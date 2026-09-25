@extends('layouts.app')

@section('title', $invoice->invoice_number)
@section('page-header', true)
@section('page-title', 'Invoice '.$invoice->invoice_number)
@section('page-subtitle', $invoice->title.' · '.($invoice->customer?->name ?? 'Customer'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="bi bi-printer"></i> Print</a>
        @can('update', $invoice)
            @if ($invoice->status === 'draft')
                <form method="POST" action="{{ route('invoices.issue', $invoice) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-primary btn-sm"><i class="bi bi-send"></i> Issue invoice</button>
                </form>
            @endif
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <x-card :padding="false">
                <div class="invoice-doc p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="lp-sidebar__mark">LP</span>
                                <div>
                                    <div class="fw-bold">{{ $company['name'] }}</div>
                                    <div class="text-muted small">{{ $company['address'] ?? 'Finance & Insurance Services' }}</div>
                                </div>
                            </div>
                            <div class="text-muted small">
                                {{ $company['email'] ?? '' }} {{ $company['phone'] ? '· '.$company['phone'] : '' }}
                                {{ $company['gst'] ? '· GST '.$company['gst'] : '' }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="h5 mb-1">INVOICE</div>
                            <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
                            <div class="text-muted small">Issued {{ \App\Support\Format::date($invoice->invoice_date) }}</div>
                            <div class="text-muted small">Due {{ \App\Support\Format::date($invoice->due_date) }}</div>
                            <div class="mt-2"><x-status-badge :status="$invoice->status" /></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase">Billed to</div>
                            <div class="fw-semibold">{{ $invoice->customer?->name }}</div>
                            <div class="small text-muted">
                                {{ $invoice->customer?->address }}<br>
                                {{ collect([$invoice->customer?->city, $invoice->customer?->pincode])->filter()->implode(' - ') }}<br>
                                {{ $invoice->customer?->mobile }} · {{ $invoice->customer?->email }}
                            </div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <div class="text-muted small text-uppercase">References</div>
                            <div class="small">
                                @if ($invoice->lead)<div>Lead: {{ $invoice->lead->lead_code }}</div>@endif
                                @if ($invoice->application)<div>Application: {{ $invoice->application->application_code }}</div>@endif
                                @if ($invoice->place_of_supply)<div>Place of supply: {{ $invoice->place_of_supply }}</div>@endif
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-end">Qty</th>
                                    <th scope="col" class="text-end">Rate</th>
                                    <th scope="col" class="text-end">Tax</th>
                                    <th scope="col" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item->description }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                        <td class="text-end">{{ \App\Support\Format::money($item->unit_price) }}</td>
                                        <td class="text-end">{{ $item->tax_rate }}%</td>
                                        <td class="text-end fw-semibold">{{ \App\Support\Format::money($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end mt-4">
                        <div class="col-sm-6 col-lg-5">
                            <div class="lp-kv"><span class="lp-kv__label">Subtotal</span><span class="lp-kv__value">{{ \App\Support\Format::money($invoice->subtotal) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Tax</span><span class="lp-kv__value">{{ \App\Support\Format::money($invoice->tax_amount) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Discount</span><span class="lp-kv__value">-{{ \App\Support\Format::money($invoice->discount) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Paid</span><span class="lp-kv__value">{{ \App\Support\Format::money($invoice->paid_amount) }}</span></div>
                            <div class="lp-kv fs-6"><span class="lp-kv__label fw-semibold">Total</span><span class="lp-kv__value text-primary fw-bold">{{ \App\Support\Format::money($invoice->total) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Balance due</span><span class="lp-kv__value fw-semibold {{ (float) $invoice->balance_amount > 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Format::money($invoice->balance_amount) }}</span></div>
                        </div>
                    </div>

                    @if ($invoice->notes || $invoice->terms)
                        <div class="lp-divider"></div>
                        @if ($invoice->notes)<div class="small mb-2"><strong>Notes:</strong> {{ $invoice->notes }}</div>@endif
                        @if ($invoice->terms)<div class="small text-muted" style="white-space:pre-line"><strong>Terms:</strong> {{ $invoice->terms }}</div>@endif
                    @endif
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Payments" icon="bi-wallet2">
                <x-slot:actions>
                    @canPermission('payments.create')
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#invoice-payment-modal">
                            <i class="bi bi-plus-lg"></i> Record
                        </button>
                    @endcanPermission
                </x-slot:actions>

                @forelse ($invoice->payments as $payment)
                    <div class="lp-kv">
                        <span class="lp-kv__label">{{ \App\Support\Format::date($payment->payment_date) }} · {{ $payment->paymentMethod?->name }}</span>
                        <span class="lp-kv__value fw-semibold">{{ \App\Support\Format::money($payment->amount) }}</span>
                    </div>
                @empty
                    <x-empty-state icon="bi-wallet2" title="No payments yet" message="Collections against this invoice will be listed here." />
                @endforelse
            </x-card>

            <x-card title="Audit" icon="bi-clock-history" class="mt-4">
                <div class="lp-kv"><span class="lp-kv__label">Created by</span><span class="lp-kv__value">{{ $invoice->creator?->name ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Created</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($invoice->created_at) }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Issued</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($invoice->issued_at) }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Paid at</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($invoice->paid_at) }}</span></div>
            </x-card>
        </div>
    </div>

    @canPermission('payments.create')
        <x-modal id="invoice-payment-modal" title="Record payment" :action="route('payments.store')" submit-label="Save payment">
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="customer_id" value="{{ $invoice->customer_id }}">

            <div class="row g-3">
                <div class="col-md-6"><x-input name="amount" label="Amount" type="number" step="0.01" :value="$invoice->balance_amount" required icon="bi-currency-rupee" /></div>
                <div class="col-md-6"><x-input name="payment_date" label="Payment date" type="date" :value="now()->toDateString()" required /></div>
                <div class="col-md-6">
                    <label class="form-label" for="payment_method_id">Payment mode<span class="req">*</span></label>
                    <select class="form-select" id="payment_method_id" name="payment_method_id" required>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><x-input name="transaction_id" label="Transaction / UTR" /></div>
                <div class="col-12"><x-textarea name="remarks" label="Remarks" rows="2" /></div>
            </div>
        </x-modal>
    @endcanPermission
@endsection
