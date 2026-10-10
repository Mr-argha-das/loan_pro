@extends('layouts.app')

@php
    $lead = $lead ?? null;
    $isEdit = (bool) $lead?->exists;
    $step = $isEdit ? max(1, min(\App\Models\Lead::LAST_STEP, (int) $step)) : 1;
    $selectedLenders = $selectedLenders ?? collect();
    $formAction = $step === 1 && ! $isEdit
        ? route('leads.store')
        : route('leads.wizard.save', ['lead' => $lead, 'step' => $step]);
@endphp

@section('title', $isEdit ? 'Lead '.$lead->lead_code : 'Create Lead')
@section('page-header', true)
@section('page-title', $isEdit ? 'Lead '.$lead->lead_code : 'Create New Lead')
@section('page-subtitle', 'Step '.$step.' of '.\App\Models\Lead::LAST_STEP.' · '.($steps[$step] ?? 'Lead Summary'))

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
                            Complete each step to save a complete lead.
                        @endif
                    </div>
                </div>
                @if ($isEdit)
                    <div class="d-flex align-items-center gap-2">
                        <x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" />
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
        lenders: '{{ route('lenders.products') }}',
        pincode: (pin) => `/pincodes/${pin}`,
    };

    const form = document.getElementById('lead-wizard-form');
    const step = {{ $step }};

    /** Product -> categories: picking Loans shows loan categories, Insurance shows insurance ones. */
    const productSelect = document.querySelector('[data-product-select]');
    const categorySelect = document.getElementById('product_category_id');
    const subcategorySelect = document.getElementById('product_subcategory_id');

    function filterCategories() {
        if (!productSelect || !categorySelect) return;

        const productId = productSelect.value;
        [...categorySelect.options].forEach((option) => {
            if (!option.value) return;
            option.hidden = productId !== '' && option.dataset.product !== productId;
        });

        if (categorySelect.selectedOptions[0]?.hidden) {
            categorySelect.value = '';
        }

        filterSubcategories();
    }

    productSelect?.addEventListener('change', filterCategories);

    /** Sub-category cascade: category -> purposes. */
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
    filterCategories();

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
        step === {{ \App\Models\Lead::LAST_STEP }} ? finishWizard() : submitStep();
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
        await submitStep({ next: {{ \App\Models\Lead::LAST_STEP }} });

        if (leadId) {
            window.location.href = `/leads/${leadId}`;
        }
    }

    /* ------------------------------------------------- pincode auto-fill */

    const pincodeInput = document.querySelector('[data-pincode]');
    const cityInput = document.querySelector('[data-pincode-city]');
    const stateInput = document.querySelector('[data-pincode-state]');

    pincodeInput?.addEventListener('input', async () => {
        const pin = pincodeInput.value.replace(/\D/g, '').slice(0, 6);
        pincodeInput.value = pin;
        if (pin.length !== 6) return;

        try {
            const payload = await LoanPro.request(saveUrls.pincode(pin));
            if (payload.found) {
                if (cityInput) cityInput.value = payload.city ?? '';
                if (stateInput) stateInput.value = payload.state ?? '';
            } else {
                LoanPro.toast('City aur state auto-fill nahi ho paaye — please manually bharein.', 'warning');
            }
        } catch (error) {
            // Lookup is a convenience only; the user can type city and state.
        }
    });

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
            monthly_income: {{ (float) ($lead?->customer?->monthly_income ?? 0) }},
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
