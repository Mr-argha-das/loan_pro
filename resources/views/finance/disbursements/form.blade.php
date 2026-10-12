@extends('layouts.app')

@php
    $isEdit = $disbursement->exists;
    $action = $isEdit ? route('disbursements.update', $disbursement) : route('disbursements.store');
@endphp

@section('title', $isEdit ? 'Edit '.$disbursement->disbursement_code : 'New Disbursement')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit disbursement' : 'New disbursement')
@section('page-subtitle', 'Capture bank details, charges and the net amount payable to the borrower.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('disbursements.index') }}">Disbursements</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $action }}" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Application & lender" icon="bi-clipboard-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            @php
                                $selectedId = (int) old('loan_application_id', $disbursement->loan_application_id);
                                $selectedApplication = $applications->firstWhere('id', $selectedId);
                                $applicationLabel = fn ($a) => trim(($a->customer?->name ?? '').' · '.($a->customer?->mobile ?? '').' · '.($a->lead?->lead_code ?? $a->application_code), ' ·');
                            @endphp
                            <label class="form-label" for="application-search">Customer (approved lead)<span class="req">*</span></label>
                            <input type="text" class="form-control" id="application-search" list="approved-application-options" autocomplete="off"
                                   placeholder="Search by customer name, mobile or lead code"
                                   value="{{ $selectedApplication ? $applicationLabel($selectedApplication) : '' }}" required>
                            <datalist id="approved-application-options">
                                @foreach ($applications as $application)
                                    <option value="{{ $applicationLabel($application) }}"
                                            data-application="{{ $application->id }}"
                                            data-customer="{{ $application->customer_id }}"
                                            data-amount="{{ $application->sanctioned_amount ?: $application->loan_amount }}"
                                            data-lender="{{ $application->primary_lender_id }}"></option>
                                @endforeach
                            </datalist>
                            <input type="hidden" name="loan_application_id" id="loan_application_id" value="{{ $selectedId ?: '' }}">
                            @if ($applications->isEmpty())
                                <div class="form-text text-danger">No approved leads are available for disbursement.</div>
                            @else
                                <div class="form-text">Only approved leads are listed. Pick one to fill the customer and loan details.</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lender_id">Disbursing lender</label>
                            <select class="form-select" id="lender_id" name="lender_id">
                                <option value="">Select lender</option>
                                @foreach ($lenders as $lender)
                                    <option value="{{ $lender->id }}" @selected((int) old('lender_id', $disbursement->lender_id) === $lender->id)>{{ $lender->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $disbursement->customer_id) }}">
                    </div>
                </x-section>

                <x-section title="Amounts" icon="bi-cash-stack" description="Net payable = disbursed amount − processing fee − other charges.">
                    <div class="row g-3">
                        <div class="col-md-3"><x-input name="approved_amount" label="Approved amount" type="number" step="0.01" :value="old('approved_amount', $disbursement->approved_amount)" required icon="bi-currency-rupee" /></div>
                        <div class="col-md-3"><x-input name="disbursed_amount" label="Disbursed amount" type="number" step="0.01" :value="old('disbursed_amount', $disbursement->disbursed_amount)" required icon="bi-currency-rupee" data-calc /></div>
                        <div class="col-md-3"><x-input name="processing_fee" label="Processing fee" type="number" step="0.01" :value="old('processing_fee', $disbursement->processing_fee)" data-calc /></div>
                        <div class="col-md-3"><x-input name="other_charges" label="Other charges" type="number" step="0.01" :value="old('other_charges', $disbursement->other_charges)" data-calc /></div>

                        <div class="col-md-3"><x-input name="disbursement_date" label="Disbursement date" type="date" :value="old('disbursement_date', $disbursement->disbursement_date?->toDateString() ?? now()->toDateString())" required /></div>
                        <div class="col-md-3">
                            <x-select name="mode" label="Mode" :options="['neft' => 'NEFT', 'rtgs' => 'RTGS', 'imps' => 'IMPS', 'upi' => 'UPI', 'cheque' => 'Cheque', 'cash' => 'Cash']"
                                      :value="old('mode', $disbursement->mode ?? 'neft')" required />
                        </div>
                        <div class="col-md-3">
                            <x-select name="status" label="Status"
                                      :options="['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', 'cancelled' => 'Cancelled']"
                                      :value="old('status', $disbursement->status ?? 'pending')" required />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Net payable</label>
                            <input type="text" class="form-control" id="net-amount-display" readonly value="{{ \App\Support\Format::money($disbursement->net_amount) }}">
                        </div>
                    </div>
                </x-section>

                <x-section title="Bank & reference" icon="bi-bank">
                    <div class="row g-3">
                        <div class="col-md-4"><x-input name="reference_number" label="Reference number" :value="old('reference_number', $disbursement->reference_number)" /></div>
                        <div class="col-md-4"><x-input name="utr_number" label="UTR number" :value="old('utr_number', $disbursement->utr_number)" /></div>
                        <div class="col-md-4"><x-input name="bank_name" label="Bank name" :value="old('bank_name', $disbursement->bank_name)" /></div>
                        <div class="col-md-4"><x-input name="bank_account_number" label="Account number" :value="old('bank_account_number', $disbursement->bank_account_number)" /></div>
                        <div class="col-md-4"><x-input name="bank_ifsc" label="IFSC code" :value="old('bank_ifsc', $disbursement->bank_ifsc)" /></div>
                        <div class="col-12"><x-textarea name="remarks" label="Remarks" rows="2" :value="old('remarks', $disbursement->remarks)" /></div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                @if ($application)
                    <x-card title="Application snapshot" icon="bi-info-circle">
                        <div class="lp-kv"><span class="lp-kv__label">Application</span><span class="lp-kv__value">{{ $application->application_code }}</span></div>
                        <div class="lp-kv"><span class="lp-kv__label">Customer</span><span class="lp-kv__value">{{ $application->customer?->name }}</span></div>
                        <div class="lp-kv"><span class="lp-kv__label">Sanctioned</span><span class="lp-kv__value">{{ \App\Support\Format::money($application->sanctioned_amount) }}</span></div>
                        <div class="lp-kv"><span class="lp-kv__label">Status</span><span class="lp-kv__value"><x-status-badge :status="$application->status" /></span></div>
                    </x-card>
                @else
                    <x-card title="Disbursement checklist" icon="bi-list-check">
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-2">Only approved applications should be disbursed.</li>
                            <li class="mb-2">Net payable is computed automatically.</li>
                            <li>Marking a disbursement <strong>Completed</strong> updates the application to Disbursed.</li>
                        </ul>
                    </x-card>
                @endif

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('disbursements.index') }}" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> {{ $isEdit ? 'Update' : 'Create' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script type="module">
    const money = (amount) => '₹' + Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const amountField = (name) => document.querySelector(`[name="${name}"]`);

    function recalc() {
        const disbursed = parseFloat(amountField('disbursed_amount')?.value || 0);
        const fee = parseFloat(amountField('processing_fee')?.value || 0);
        const other = parseFloat(amountField('other_charges')?.value || 0);
        document.getElementById('net-amount-display').value = money(Math.max(0, disbursed - fee - other));
    }

    document.querySelectorAll('[data-calc]').forEach((field) => field.addEventListener('input', recalc));

    // Pick an approved lead from the searchable field: fills application, customer, amount and lender.
    const applicationSearch = document.getElementById('application-search');
    applicationSearch?.addEventListener('input', () => {
        const match = [...document.querySelectorAll('#approved-application-options option')]
            .find((opt) => opt.value === applicationSearch.value);

        document.getElementById('loan_application_id').value = match?.dataset.application ?? '';
        document.getElementById('customer_id').value = match?.dataset.customer ?? '';
        if (!match) return;

        amountField('approved_amount').value = match.dataset.amount ?? '';
        amountField('disbursed_amount').value = match.dataset.amount ?? '';
        document.getElementById('lender_id').value = match.dataset.lender ?? '';
        recalc();
    });

    recalc();
</script>
@endpush
