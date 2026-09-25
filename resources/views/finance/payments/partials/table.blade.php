@php $items = $items ?? ($payments ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Reference</th>
                <th scope="col">Customer</th>
                <th scope="col">Invoice</th>
                <th scope="col">Date</th>
                <th scope="col">Mode</th>
                <th scope="col">Amount</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $payment)
                <tr>
                    <td>
                        <a href="{{ route('payments.show', $payment) }}" class="fw-semibold">{{ $payment->payment_code }}</a>
                        <div class="text-muted" style="font-size:.73rem">{{ $payment->transaction_id ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $payment->customer?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $payment->customer?->mobile }}</div>
                    </td>
                    <td>
                        @if ($payment->invoice)
                            <a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a>
                        @else — @endif
                    </td>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($payment->payment_date) }}</td>
                    <td>{{ $payment->paymentMethod?->name ?? '—' }}</td>
                    <td class="fw-semibold text-success">{{ \App\Support\Format::money($payment->amount) }}</td>
                    <td><x-status-badge :status="$payment->status" /></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                            @can('update', $payment)
                                <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $payment)
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" class="d-inline"
                                      data-confirm="Delete payment {{ $payment->payment_code }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <x-empty-state icon="bi-wallet2" title="No payments recorded" message="Collections appear here as soon as they are recorded against an invoice.">
                            <x-slot:action>
                                @can('create', App\Models\Payment::class)
                                    <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Record payment</a>
                                @endcan
                            </x-slot:action>
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
