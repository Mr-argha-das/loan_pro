@extends('layouts.app')

@section('title', $payment->payment_code)
@section('page-header', true)
@section('page-title', 'Payment '.$payment->payment_code)
@section('page-subtitle', ($payment->customer?->name ?? 'Customer').' · '.($payment->paymentMethod?->name ?? 'Mode pending'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $payment->payment_code }}</li>
@endsection

@section('page-actions')
    @can('update', $payment)
        <a href="{{ route('payments.edit', $payment) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    @endcan
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Receipt details" icon="bi-wallet2">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3"><x-stat-card label="Amount" :value="\App\Support\Format::money($payment->amount)" icon="bi-currency-rupee" tone="green" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Mode" :value="$payment->paymentMethod?->name ?? '—'" icon="bi-credit-card" tone="blue" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Date" :value="\App\Support\Format::date($payment->payment_date)" icon="bi-calendar3" tone="primary" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Status" :value="\App\Support\Format::titleCase($payment->status)" icon="bi-patch-check" tone="orange" /></div>
                </div>

                <div class="row g-2 small">
                    <div class="col-6 col-md-4"><span class="text-muted">Customer:</span> <span class="fw-semibold">{{ $payment->customer?->name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4">
                        <span class="text-muted">Invoice:</span>
                        <span class="fw-semibold">
                            @if ($payment->invoice)<a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a>@else — @endif
                        </span>
                    </div>
                    <div class="col-6 col-md-4"><span class="text-muted">Application:</span> <span class="fw-semibold">{{ $payment->application?->application_code ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Transaction ID:</span> <span class="fw-semibold">{{ $payment->transaction_id ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Reference:</span> <span class="fw-semibold">{{ $payment->reference_number ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Bank:</span> <span class="fw-semibold">{{ $payment->bank_name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Cheque:</span> <span class="fw-semibold">{{ $payment->cheque_number ?? '—' }}{{ $payment->cheque_date ? ' · '.\App\Support\Format::date($payment->cheque_date) : '' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Received by:</span> <span class="fw-semibold">{{ $payment->receiver?->name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Recorded:</span> <span class="fw-semibold">{{ \App\Support\Format::dateTime($payment->created_at) }}</span></div>
                </div>

                @if ($payment->remarks)
                    <div class="lp-divider"></div>
                    <div class="small"><strong>Remarks:</strong> {{ $payment->remarks }}</div>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            @if ($payment->invoice)
                <x-card title="Invoice summary" icon="bi-receipt">
                    <div class="lp-kv"><span class="lp-kv__label">Invoice</span><span class="lp-kv__value">{{ $payment->invoice->invoice_number }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Total</span><span class="lp-kv__value">{{ \App\Support\Format::money($payment->invoice->total) }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Paid</span><span class="lp-kv__value">{{ \App\Support\Format::money($payment->invoice->paid_amount) }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Balance</span><span class="lp-kv__value fw-semibold">{{ \App\Support\Format::money($payment->invoice->balance_amount) }}</span></div>
                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="btn btn-light w-100 mt-2"><i class="bi bi-box-arrow-up-right"></i> Open invoice</a>
                </x-card>
            @endif

            <x-card title="Related documents" icon="bi-folder-check" class="mt-4">
                @forelse ($payment->documents as $document)
                    <div class="lp-kv">
                        <span class="lp-kv__label">{{ $document->documentType?->name ?? 'Document' }}</span>
                        <span class="lp-kv__value">
                            <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener">View</a>
                        </span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No receipts attached to this payment.</p>
                @endforelse
            </x-card>
        </div>
    </div>
@endsection
