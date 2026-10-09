@php $items = $items ?? ($leads ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <x-sortable-th column="lead_code" label="Lead ID" />
                <th scope="col">Customer</th>
                <th scope="col">Product / Category</th>
                <x-sortable-th column="loan_amount" label="Amount" />
                <x-sortable-th column="status" label="Status" />
                <th scope="col">Owner</th>
                <x-sortable-th column="created_at" label="Created" />
                <th scope="col">Closed</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $lead)
                <tr>
                    <td>
                        <a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $lead->customer?->name }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $lead->customer?->mobile }}</div>
                    </td>
                    <td>
                        <div>{{ $lead->category?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $lead->subcategory?->name }}</div>
                    </td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                    <td><x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" /></td>
                    <td>{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                    <td class="text-muted">{{ $lead->created_at?->format('d M Y') }}</td>
                    <td class="text-muted" title="Date of the last status change">
                        {{ $lead->last_status_at ? \Illuminate\Support\Carbon::parse($lead->last_status_at)->format('d M Y') : '—' }}
                    </td>
                    <td class="text-end">
                        <x-dropdown>
                            <li><a class="dropdown-item" href="{{ route('leads.show', $lead) }}"><i class="bi bi-eye"></i> View details</a></li>
                            <li><button class="dropdown-item" type="button" data-quick-view="{{ route('leads.quick-view', $lead) }}"><i class="bi bi-lightning"></i> Quick view</button></li>

                            @can('update', $lead)
                                <li><a class="dropdown-item" href="{{ route('leads.wizard', ['lead' => $lead, 'step' => $lead->current_step]) }}"><i class="bi bi-pencil-square"></i> Edit details</a></li>
                                <li><button class="dropdown-item" type="button" data-status-modal="{{ route('leads.status', $lead) }}"><i class="bi bi-arrow-repeat"></i> Change status</button></li>
                            @endcan

                            @can('assign', $lead)
                                <li><button class="dropdown-item" type="button" data-assign-modal="{{ route('leads.assign', $lead) }}"><i class="bi bi-person-plus"></i> Assign employee</button></li>
                            @endcan

                            @can('delete', $lead)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('leads.destroy', $lead) }}">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger" data-confirm="Delete lead {{ $lead->lead_code }}? This can be restored by an administrator."><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </li>
                            @endcan
                        </x-dropdown>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <x-empty-state icon="bi-funnel" title="No leads match your filters" message="Try clearing the filters or create a new lead.">
                            <x-slot:action>
                                @canPermission('leads.create')
                                    <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Lead</a>
                                @endcanPermission
                            </x-slot:action>
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
