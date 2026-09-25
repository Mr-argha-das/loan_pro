@php $items = $items ?? ($invoices ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Invoice</th>
                <th scope="col">Customer</th>
                <th scope="col">Date / Due</th>
                <th scope="col">Total</th>
                <th scope="col">Paid</th>
                <th scope="col">Balance</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $invoice)
                <tr>
                    <td>
                        <a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold">{{ $invoice->invoice_number }}</a>
                        <div class="text-muted" style="font-size:.73rem">{{ $invoice->title }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $invoice->customer?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $invoice->customer?->mobile }}</div>
                    </td>
                    <td class="text-nowrap">
                        <div>{{ \App\Support\Format::date($invoice->invoice_date) }}</div>
                        <div class="text-muted" style="font-size:.73rem">Due {{ \App\Support\Format::date($invoice->due_date) }}</div>
                    </td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($invoice->total) }}</td>
                    <td>{{ \App\Support\Format::money($invoice->paid_amount) }}</td>
                    <td class="fw-semibold {{ (float) $invoice->balance_amount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ \App\Support\Format::money($invoice->balance_amount) }}
                    </td>
                    <td><x-status-badge :status="$invoice->status" /></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-light" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('invoices.print', $invoice) }}" target="_blank" rel="noopener"><i class="bi bi-printer me-2"></i>Print</a></li>
                                @can('update', $invoice)
                                    <li><a class="dropdown-item" href="{{ route('invoices.edit', $invoice) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                @endcan
                                @canPermission('payments.create')
                                    <li><a class="dropdown-item" href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}"><i class="bi bi-wallet2 me-2"></i>Record payment</a></li>
                                @endcanPermission
                                @can('delete', $invoice)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" data-confirm="Delete invoice {{ $invoice->invoice_number }}?">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                        </form>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <x-empty-state icon="bi-receipt" title="No invoices found" message="Create an invoice for processing fees, premiums and charges.">
                            <x-slot:action>
                                @can('create', App\Models\Invoice::class)
                                    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create invoice</a>
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
