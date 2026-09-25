@php $items = $items ?? ($customers ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <x-sortable-th column="customer_code" label="Customer" />
                <th scope="col">Contact</th>
                <th scope="col">City</th>
                <th scope="col">Employment</th>
                <x-sortable-th column="monthly_income" label="Monthly income" />
                <th scope="col">KYC</th>
                <th scope="col">Relationship</th>
                <th scope="col">Leads / Apps</th>
                <x-sortable-th column="created_at" label="Added" />
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $customer)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="lp-avatar">{{ $customer->initials() }}</span>
                            <div>
                                <a href="{{ route('customers.show', $customer) }}" class="fw-semibold">{{ $customer->name }}</a>
                                <div class="text-muted" style="font-size:.73rem">{{ $customer->customer_code }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $customer->mobile }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $customer->email ?? '—' }}</div>
                    </td>
                    <td>{{ $customer->city ?? '—' }}</td>
                    <td>
                        <div>{{ $customer->employmentType?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $customer->company_name ?? '' }}</div>
                    </td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($customer->monthly_income) }}</td>
                    <td><x-status-badge :status="$customer->kyc_status" /></td>
                    <td>{{ $customer->assignedEmployee?->name ?? 'Unassigned' }}</td>
                    <td>
                        <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">{{ (int) ($customer->leads_count ?? $customer->leads->count()) }}</span>
                        <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">{{ (int) ($customer->applications_count ?? $customer->applications->count()) }}</span>
                    </td>
                    <td class="text-muted text-nowrap">{{ $customer->created_at?->format('d M Y') }}</td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-light" title="View profile" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>
                            @can('update', $customer)
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-light" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @canPermission('leads.create')
                                <a href="{{ route('leads.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-light" title="Create lead" data-bs-toggle="tooltip"><i class="bi bi-plus-circle"></i></a>
                            @endcanPermission
                            <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}"><i class="bi bi-person-lines-fill me-2"></i>Open CRM profile</a></li>
                                <li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}#documents"><i class="bi bi-folder-check me-2"></i>Documents</a></li>
                                <li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}#payments"><i class="bi bi-cash-stack me-2"></i>Payments</a></li>
                                @can('delete', $customer)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Delete {{ $customer->name }}? Linked records stay for audit.">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete customer</button>
                                        </form>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <x-empty-state icon="bi-people" title="No customers found" message="Adjust the filters or add a new customer profile.">
                            <x-slot:action>
                                @can('create', App\Models\Customer::class)
                                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add customer</a>
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
