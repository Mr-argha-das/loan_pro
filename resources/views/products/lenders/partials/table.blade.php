@php $items = $items ?? ($lenders ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Lender</th>
                <th scope="col">Type</th>
                <th scope="col">Online</th>
                <th scope="col">Contact</th>
                <th scope="col">Ticket size</th>
                <th scope="col">Products</th>
                <th scope="col">TAT</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $lender)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="lp-lender-card__logo">{{ $lender->code ?? strtoupper(substr($lender->name, 0, 2)) }}</span>
                            <div>
                                <a href="{{ route('lenders.show', $lender) }}" class="fw-semibold">{{ $lender->name }}</a>
                                <div class="text-muted" style="font-size:.73rem">{{ $lender->city ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ \App\Support\Format::titleCase($lender->lender_type) }}</td>
                    <td>
                        <span class="lp-badge bg-{{ $lender->online_status === 'online' ? 'success' : 'secondary' }}-subtle bg-opacity-10 text-{{ $lender->online_status === 'online' ? 'success' : 'secondary' }}">
                            <i class="bi bi-{{ $lender->online_status === 'online' ? 'wifi' : 'building' }}"></i>
                            {{ ucfirst($lender->online_status ?? 'offline') }}
                        </span>
                    </td>
                    <td>
                        <div>{{ $lender->contact_person ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $lender->contact_number ?? '' }}</div>
                    </td>
                    <td>{{ \App\Support\Format::compactInr($lender->min_ticket_size) }} – {{ \App\Support\Format::compactInr($lender->max_ticket_size) }}</td>
                    <td><span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">{{ $lender->products_count ?? $lender->products->count() }}</span></td>
                    <td>{{ $lender->processing_time_days ? $lender->processing_time_days.' days' : '—' }}</td>
                    <td><x-status-badge :status="$lender->status" /></td>
                    <td class="text-end">
                        <a href="{{ route('lenders.show', $lender) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty-state icon="bi-bank" title="No lenders found" message="Adjust the filters or onboard a new lender." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
