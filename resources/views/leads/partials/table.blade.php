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
                <x-sortable-th column="priority" label="Priority" />
                <th scope="col">Owner</th>
                <th scope="col">Step</th>
                <x-sortable-th column="created_at" label="Created" />
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $lead)
                <tr>
                    <td>
                        <a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a>
                        @if ($lead->is_otp_verified)
                            <i class="bi bi-patch-check-fill text-success ms-1" data-bs-toggle="tooltip" title="Mobile verified"></i>
                        @endif
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
                    <td>
                        <x-status-badge :status="$lead->priority" />
                    </td>
                    <td>{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                    <td>
                        <div class="progress" style="width:64px" role="progressbar" aria-valuenow="{{ $lead->progressPercent() }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-primary" style="width: {{ $lead->progressPercent() }}%"></div>
                        </div>
                        <div class="text-muted" style="font-size:.7rem">{{ $lead->current_step }}/10 &middot; {{ $lead->stepName() }}</div>
                    </td>
                    <td class="text-muted">{{ $lead->created_at?->format('d M Y') }}</td>
                    <td class="text-end">
                        <x-dropdown>
                            <li><a class="dropdown-item" href="{{ route('leads.show', $lead) }}"><i class="bi bi-eye"></i> View details</a></li>
                            <li><button class="dropdown-item" type="button" data-quick-view="{{ route('leads.quick-view', $lead) }}"><i class="bi bi-lightning"></i> Quick view</button></li>

                            @can('update', $lead)
                                <li><a class="dropdown-item" href="{{ route('leads.wizard', ['lead' => $lead, 'step' => $lead->current_step]) }}"><i class="bi bi-pencil-square"></i> Continue wizard</a></li>
                                <li><button class="dropdown-item" type="button" data-status-modal="{{ route('leads.status', $lead) }}"><i class="bi bi-arrow-repeat"></i> Change status</button></li>
                            @endcan

                            @can('assign', $lead)
                                <li><button class="dropdown-item" type="button" data-assign-modal="{{ route('leads.assign', $lead) }}"><i class="bi bi-person-plus"></i> Assign employee</button></li>
                            @endcan

                            @can('convert', $lead)
                                <li>
                                    <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                        @csrf
                                        <button class="dropdown-item text-success" data-confirm="Create a loan application from {{ $lead->lead_code }}?" data-confirm-title="Convert lead">
                                            <i class="bi bi-arrow-right-circle"></i> Convert to application
                                        </button>
                                    </form>
                                </li>
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
                    <td colspan="10">
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
