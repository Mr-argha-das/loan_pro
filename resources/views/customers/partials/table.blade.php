@php $items = $items ?? ($customers ?? collect()); @endphp
<div class="lp-table-wrap">
<table class="lp-table"><thead><tr>
    <x-sortable-th column="customer_code" label="Customer" /><th>Contact</th><th>City</th><th>Employment</th>
    <x-sortable-th column="monthly_income" label="Monthly income" /><th>Relationship</th><th>Lead status</th><th>Close date</th>
    <x-sortable-th column="created_at" label="Added" /><th class="text-end">Actions</th>
</tr></thead><tbody>
@forelse ($items as $customer)
    @php $latestLead = $customer->leads->sortByDesc('created_at')->first(); $lastChange = $latestLead?->statusHistories?->sortByDesc('created_at')->first(); @endphp
    <tr>
        <td><div class="d-flex align-items-center gap-2"><span class="lp-avatar">{{ $customer->initials() }}</span><div><a href="{{ route('customers.show', $customer) }}" class="fw-semibold">{{ $customer->name }}</a><div class="text-muted" style="font-size:.73rem">{{ $customer->customer_code }}</div></div></div></td>
        <td><div>{{ $customer->mobile }}</div><div class="text-muted" style="font-size:.73rem">{{ $customer->email ?? '—' }}</div></td>
        <td>{{ $customer->city ?? '—' }}</td>
        <td><div>{{ $customer->employmentType?->name ?? '—' }}</div><div class="text-muted" style="font-size:.73rem">{{ $customer->company_name ?? '' }}</div></td>
        <td class="fw-semibold">{{ \App\Support\Format::money($customer->monthly_income) }}</td>
        <td>{{ $customer->assignedEmployee?->name ?? 'Unassigned' }}</td>
        <td><x-status-badge :status="$latestLead?->status ?? 'pending'" :label="$latestLead?->leadStatus?->name ?? 'No lead'" /></td>
        <td class="text-muted text-nowrap">{{ in_array($latestLead?->status, ['closed','rejected','cancelled'], true) ? ($lastChange?->created_at?->format('d M Y') ?? '—') : '—' }}</td>
        <td class="text-muted text-nowrap">{{ $customer->created_at?->format('d M Y') }}</td>
        <td class="text-end"><div class="btn-group">
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
            @can('update', $customer)<a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
            <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
            <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}#documents"><i class="bi bi-folder-check me-2"></i>Documents</a></li><li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}#payments"><i class="bi bi-cash-stack me-2"></i>Payments</a></li>
            @can('delete', $customer)<li><hr class="dropdown-divider"></li><li><form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return window.confirm('Delete {{ $customer->name }}?');">@csrf @method('DELETE')<button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete customer</button></form></li>@endcan
            </ul>
        </div></td>
    </tr>
@empty
    <tr><td colspan="10"><x-empty-state icon="bi-people" title="No customers found" message="Adjust the filters or add a new customer profile." /></td></tr>
@endforelse
</tbody></table></div>
@include('partials.pagination-bar', ['items' => $items])
