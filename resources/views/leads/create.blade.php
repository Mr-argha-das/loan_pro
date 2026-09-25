@extends('layouts.app')

@php
    $lead = $lead ?? null;
    $isEdit = (bool) $lead?->exists;
    $step = $isEdit ? max(1, min(10, (int) $step)) : 1;
    $selectedLenders = $selectedLenders ?? collect();
    $formAction = $step === 1 && ! $isEdit
        ? route('leads.store')
        : route('leads.wizard.save', ['lead' => $lead, 'step' => $step]);
@endphp

@section('title', $isEdit ? 'Lead '.$lead->lead_code : 'Create Lead')
@section('page-header', true)
@section('page-title', $isEdit ? 'Lead '.$lead->lead_code : 'Create New Lead')
@section('page-subtitle', 'Step '.$step.' of 10 · '.($steps[$step] ?? 'Lead Summary'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? $lead->lead_code : 'Create' }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Cancel</a>
@endsection

@section('content')
    <div class="lp-card mb-4 no-print">
        <div class="lp-card__body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <div class="fw-semibold">Onboarding progress</div>
                    <div class="text-muted small">
                        @if ($isEdit)
                            {{ $lead->progressPercent() }}% complete · Saved {{ $lead->updated_at?->diffForHumans() }}
                        @else
                            Complete each step to build a lender-ready application.
                        @endif
                    </div>
                </div>
                @if ($isEdit)
                    <div class="d-flex align-items-center gap-2">
                        <x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" />
                        @if ($lead->is_otp_verified)
                            <span class="lp-badge bg-success-subtle text-success bg-opacity-10"><i class="bi bi-patch-check-fill"></i> Mobile verified</span>
                        @else
                            <span class="lp-badge bg-warning-subtle text-warning bg-opacity-10"><i class="bi bi-shield-exclamation"></i> OTP pending</span>
                        @endif
                    </div>
                @endif
            </div>

            <x-stepper :steps="$steps" :current="$step" :max-reached="$isEdit ? $lead->current_step : 1"
                       :url="fn ($n) => $isEdit ? route('leads.wizard', ['lead' => $lead, 'step' => $n]) : '#'" />
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="lead-wizard-form" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($step === 1 && ! $isEdit)
            <input type="hidden" name="customer_type" value="new">
            <input type="hidden" name="lead_type" value="fresh">
        @endif

        @includeIf('leads.partials.steps.'.$step)
    </form>
@endsection

@push('scripts')
<script type="module">
    const leadId = {{ $isEdit ? $lead->id : 'null' }};
    const wizardSteps = @json($steps);
    const saveUrls = {
        store: '{{ route('leads.store') }}',
        step: (step) => leadId ? `/leads/${leadId}/wizard/${step}` : '{{ route('leads.store') }}',
        otpSend: leadId ? `/leads/${leadId}/otp` : null,
        otpVerify: leadId ? `/leads/${leadId}/otp/verify` : null,
        lenders: '{{ route('lenders.products') }}',
    };

    const form = document.getElementById('lead-wizard-form');
    const step = {{ $step }};

    /** Sub-category cascade: category -> purposes. */
    const categorySelect = document.getElementById('product_category_id');
    const subcategorySelect = document.getElementById('product_subcategory_id');

    function filterSubcategories() {
        if (!categorySelect || !subcategorySelect) return;

        const categoryId = categorySelect.value;
        [...subcategorySelect.options].forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.category !== categoryId && categoryId !== '';
        });

        if (subcategorySelect.selectedOptions[0]?.hidden) {
            subcategorySelect.value = '';
        }
    }

    categorySelect?.addEventListener('change', filterSubcategories);
    filterSubcategories();

    /* --------------------------------------------------------- save actions */

    async function submitStep({ draft = false, next = null } = {}) {
        const data = new FormData(form);
        data.set('is_draft', draft ? '1' : '0');

        const targetStep = next ?? step;

        try {
            const payload = await LoanPro.request(saveUrls.step(targetStep), { method: 'POST', body: data });

            if (step === 1 && !leadId && payload.lead_id) {
                window.location.href = `/leads/${payload.lead_id}/wizard/2`;
                return;
            }

            if (draft) {
                LoanPro.toast(payload.message ?? 'Draft saved.');
                return;
            }

            window.location.href = payload.next_url;
        } catch (error) {
            const errors = error.payload?.errors ?? {};
            const messages = Object.values(errors).flat();

            if (messages.length) {
                messages.slice(0, 4).forEach((message) => LoanPro.toast(message, 'danger'));
                document.querySelector('.is-invalid')?.focus();
            } else {
                LoanPro.toast(error.message, 'danger');
            }
        }
    }

    form?.addEventListener('submit', (event) => {
        // Buttons that override the action (e.g. "Save & Create Application") are posted
        // natively so the server redirect to the new application is followed.
        if (event.submitter?.getAttribute('formaction')) {
            return;
        }

        event.preventDefault();
        try {
            collectSelectedLenders();
        } catch (error) {
            console.warn('Lender serialisation skipped', error);
        }
        step === 10 ? finishWizard() : submitStep();
    });

    document.querySelector('[data-save-draft]')?.addEventListener('click', () => {
        try {
            collectSelectedLenders();
        } catch (error) {
            console.warn('Lender serialisation skipped', error);
        }
        submitStep({ draft: true });
    });

    document.querySelector('[data-wizard-back]')?.addEventListener('click', () => {
        if (leadId) {
            window.location.href = `/leads/${leadId}/wizard/${Math.max(1, step - 1)}`;
        } else {
            window.history.back();
        }
    });

    async function finishWizard() {
        await submitStep({ next: 10 });

        if (leadId) {
            window.location.href = `/leads/${leadId}`;
        }
    }

    /* --------------------------------------------------------- OTP handling */

    const otpInputs = document.querySelectorAll('[data-otp]');
    const otpValue = () => [...otpInputs].map((input) => input.value).join('');

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 1);
            if (input.value && otpInputs[index + 1]) otpInputs[index + 1].focus();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && otpInputs[index - 1]) {
                otpInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (event) => {
            const digits = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            if (!digits) return;
            event.preventDefault();
            digits.split('').forEach((digit, i) => { if (otpInputs[i]) otpInputs[i].value = digit; });
            otpInputs[Math.min(digits.length, 5)].focus();
        });
    });

    let otpTimer = null;

    function startOtpTimer(seconds) {
        const label = document.querySelector('[data-otp-timer]');
        const resend = document.querySelector('[data-otp-resend]');

        clearInterval(otpTimer);
        resend?.classList.add('disabled');
        let remaining = seconds;

        const tick = () => {
            const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
            const secs = String(remaining % 60).padStart(2, '0');
            if (label) label.textContent = `OTP expires in ${minutes}:${secs}`;
            if (remaining <= 0) {
                clearInterval(otpTimer);
                resend?.classList.remove('disabled');
                if (label) label.textContent = 'OTP expired — request a new code';
                return;
            }
            remaining -= 1;
        };

        tick();
        otpTimer = setInterval(tick, 1000);
    }

    document.querySelector('[data-otp-send]')?.addEventListener('click', async (event) => {
        event.preventDefault();

        try {
            const payload = await LoanPro.request(saveUrls.otpSend, { method: 'POST' });
            LoanPro.toast(payload.message);
            startOtpTimer(payload.expires_in ?? 120);
            otpInputs[0]?.focus();

            if (payload.demo_code) {
                LoanPro.toast(`Demo mode: use OTP ${payload.demo_code}`, 'info', 'Development');
            }
        } catch (error) {
            LoanPro.toast(error.message, 'danger');
        }
    });

    document.querySelector('[data-otp-verify]')?.addEventListener('click', async (event) => {
        event.preventDefault();
        const code = otpValue();

        if (code.length !== 6) {
            LoanPro.toast('Please enter all 6 digits of the OTP.', 'warning');
            return;
        }

        try {
            const payload = await LoanPro.request(saveUrls.otpVerify, { method: 'POST', body: { otp: code } });
            LoanPro.toast(payload.message);
            setTimeout(() => { window.location.href = payload.next_url; }, 700);
        } catch (error) {
            LoanPro.toast(error.message, 'danger');
        }
    });

    document.querySelector('[data-otp-change-number]')?.addEventListener('click', () => {
        if (leadId) window.location.href = `/leads/${leadId}/wizard/1`;
    });

    if (step === 3 && leadId) {
        startOtpTimer(120);
    }

    /* -------------------------------------------------- lender selection */

    const lenderResults = document.getElementById('lender-results');
    const selectedLenderIds = new Set(@json($selectedLenders?->pluck('lender_product_id')->filter()->values() ?? []));

    async function loadLenders(params = {}) {
        if (!lenderResults) return;

        lenderResults.innerHTML = '<div class="lp-skeleton mb-3" style="height:120px"></div><div class="lp-skeleton mb-3" style="height:120px"></div>';

        const query = new URLSearchParams({
            product_id: document.getElementById('lender-filter-product')?.value ?? '',
            category_id: document.getElementById('lender-filter-category')?.value ?? '',
            loan_type: document.getElementById('lender-filter-type')?.value ?? '',
            q: document.getElementById('lender-filter-search')?.value ?? '',
            sort: document.getElementById('lender-filter-sort')?.value ?? 'roi',
            direction: document.getElementById('lender-filter-direction')?.value ?? 'asc',
            loan_amount: document.getElementById('loan_amount')?.value ?? {{ (int) ($lead->loan_amount ?? 500000) }},
            tenure_months: document.getElementById('tenure_months')?.value ?? {{ (int) ($lead->tenure_months ?? 36) }},
            ...params,
        });

        const payload = await LoanPro.request(`${saveUrls.lenders}?${query}`);
        lenderResults.innerHTML = payload.html;
        document.getElementById('lender-count').textContent = payload.count;
        bindLenderCards();
        updateSelectedCount();
    }

    function bindLenderCards() {
        document.querySelectorAll('[data-lender-card]').forEach((card) => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            checkbox.checked = selectedLenderIds.has(Number(card.dataset.lenderProductId));
            card.classList.toggle('is-selected', checkbox.checked);
        });

        document.querySelectorAll('[data-lender-toggle]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const card = checkbox.closest('[data-lender-card]');
                const id = Number(card.dataset.lenderProductId);

                if (checkbox.checked) {
                    // A lead can only carry one product per lender (DB unique key), so
                    // selecting a product replaces any other product of the same lender.
                    document.querySelectorAll(`[data-lender-card][data-lender-id="${card.dataset.lenderId}"]`).forEach((sibling) => {
                        if (sibling === card) return;
                        const siblingBox = sibling.querySelector('input[type="checkbox"]');
                        if (siblingBox?.checked) {
                            siblingBox.checked = false;
                            sibling.classList.remove('is-selected');
                            selectedLenderIds.delete(Number(sibling.dataset.lenderProductId));
                        }
                    });
                    selectedLenderIds.add(id);
                } else {
                    selectedLenderIds.delete(id);
                }

                card.classList.toggle('is-selected', checkbox.checked);
                updateSelectedCount();
            });
        });
    }

    /** Live count of shortlisted products shown next to the filters. */
    function updateSelectedCount() {
        const target = document.getElementById('lender-selected-count');
        if (target) target.textContent = selectedLenderIds.size;
    }

    /**
     * Selected lenders are serialised into hidden inputs just before the form
     * is submitted, so the server receives a normal `lenders[]` payload.
     */
    function collectSelectedLenders() {
        const container = document.getElementById('lender-hidden-inputs');

        if (!container) return;

        // Cards can be filtered out of the current view — only serialise what is on screen.
        const holders = [...selectedLenderIds]
            .map((id) => document.querySelector(`[data-lender-card][data-lender-product-id="${id}"]`))
            .filter(Boolean);

        container.innerHTML = holders.map((card, index) => {
            const preview = card.querySelector('[data-lender-preview]');

            return `
                <input type="hidden" name="lenders[${index}][lender_id]" value="${card.dataset.lenderId}">
                <input type="hidden" name="lenders[${index}][lender_product_id]" value="${card.dataset.lenderProductId}">
                <input type="hidden" name="lenders[${index}][loan_amount]" value="${preview?.dataset.amount ?? ''}">
                <input type="hidden" name="lenders[${index}][tenure_months]" value="${preview?.dataset.tenure ?? ''}">
                <input type="hidden" name="lenders[${index}][roi]" value="${card.dataset.roi ?? ''}">
                <input type="hidden" name="lenders[${index}][apr]" value="${card.dataset.apr ?? ''}">
                <input type="hidden" name="lenders[${index}][processing_fee]" value="${card.dataset.processingFee ?? ''}">
                <input type="hidden" name="lenders[${index}][penal_charge]" value="${card.dataset.penalCharge ?? ''}">
            `;
        }).join('');
    }

    document.querySelectorAll('[data-lender-filter]').forEach((input) => {
        input.addEventListener('change', () => loadLenders());
    });

    document.getElementById('lender-filter-search')?.addEventListener('input', debounce(() => loadLenders(), 320));

    function debounce(fn, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }

    if (lenderResults) {
        loadLenders();
    }
</script>
@endpush
