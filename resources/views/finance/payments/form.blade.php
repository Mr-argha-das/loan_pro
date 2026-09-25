@extends('layouts.app')

@php $isEdit = $payment->exists; @endphp

@section('title', $isEdit ? 'Edit '.$payment->payment_code : 'Record Payment')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit payment '.$payment->payment_code : 'Record a payment')
@section('page-subtitle', 'Every collection is linked to an invoice so balances update automatically.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $isEdit ? route('payments.update', $payment) : route('payments.store') }}" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Payment details" icon="bi-wallet2">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_id">Customer<span class="req">*</span></label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">Select customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((int) old('customer_id', $payment->customer_id) === $customer->id)>
                                        {{ $customer->name }} — {{ $customer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="invoice_id">Invoice</label>
                            <select class="form-select" id="invoice_id" name="invoice_id">
                                <option value="">No invoice (advance receipt)</option>
                                @foreach ($invoices as $invoice)
                                    <option value="{{ $invoice->id }}" data-balance="{{ $invoice->balance_amount }}"
                                            @selected((int) old('invoice_id', $payment->invoice_id) === $invoice->id)>
                                        {{ $invoice->invoice_number }} — balance {{ \App\Support\Format::money($invoice->balance_amount) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4"><x-input name="amount" label="Amount" type="number" step="0.01" min="0" :value="old('amount', $payment->amount)" required icon="bi-currency-rupee" /></div>
                        <div class="col-md-4"><x-input name="payment_date" label="Payment date" type="date" :value="old('payment_date', $payment->payment_date?->toDateString() ?? now()->toDateString())" required /></div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment_method_id">Payment mode<span class="req">*</span></label>
                            <select class="form-select" id="payment_method_id" name="payment_method_id" required>
                                @foreach ($methods as $method)
                                    <option value="{{ $method->id }}" @selected((int) old('payment_method_id', $payment->payment_method_id) === $method->id)>{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4"><x-input name="transaction_id" label="Transaction / UTR" :value="old('transaction_id', $payment->transaction_id)" /></div>
                        <div class="col-md-4"><x-input name="reference_number" label="Reference number" :value="old('reference_number', $payment->reference_number)" /></div>
                        <div class="col-md-4"><x-input name="bank_name" label="Bank name" :value="old('bank_name', $payment->bank_name)" /></div>

                        <div class="col-md-4"><x-input name="cheque_number" label="Cheque number" :value="old('cheque_number', $payment->cheque_number)" /></div>
                        <div class="col-md-4"><x-input name="cheque_date" label="Cheque date" type="date" :value="old('cheque_date', $payment->cheque_date?->toDateString())" /></div>
                        <div class="col-md-4">
                            <x-select name="status" label="Status" :options="['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed', 'refunded' => 'Refunded']"
                                      :value="old('status', $payment->status ?? 'completed')" />
                        </div>

                        <div class="col-12"><x-textarea name="remarks" label="Remarks" rows="2" :value="old('remarks', $payment->remarks)" /></div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                <x-section title="Guidance" icon="bi-info-circle">
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Selecting an invoice pre-fills the outstanding balance.</li>
                        <li class="mb-2">Part payments are supported — the invoice moves to <strong>Partially Paid</strong> until settled.</li>
                        <li>Cheque payments should stay <strong>Pending</strong> until realisation.</li>
                    </ul>
                </x-section>

                <div class="d-flex gap-2">
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> {{ $isEdit ? 'Update' : 'Save payment' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script type="module">
    const invoice = document.getElementById('invoice_id');
    const amount = document.querySelector('[name="amount"]');

    invoice?.addEventListener('change', () => {
        const balance = invoice.selectedOptions[0]?.dataset.balance;
        if (balance && amount && !amount.value) {
            amount.value = balance;
        }
    });
</script>
@endpush
