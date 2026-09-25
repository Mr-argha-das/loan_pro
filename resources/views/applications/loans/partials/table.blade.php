@php $items = $items ?? ($applications ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <x-sortable-th column="application_code" label="Application" />
                <th scope="col">Customer</th>
                <th scope="col">Lender / Category</th>
                <x-sortable-th column="loan_amount" label="Amount" />
                <th scope="col">EMI</th>
                <x-sortable-th column="status" label="Status" />
                <th scope="col">Owner</th>
                <x-sortable-th column="created_at" label="Created" />
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $application)
                <tr>
                    <td>
                        <a href="{{ route('applications.show', $application) }}" class="fw-semibold">{{ $application->application_code }}</a>
                        <div class="text-muted" style="font-size:.73rem">{{ $application->lead?->lead_code ?? 'Direct' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $application->customer?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $application->customer?->mobile }}</div>
                    </td>
                    <td>
                        <div>{{ $application->lender?->name ?? $application->preferred_bank ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $application->category?->name }}</div>
                    </td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($application->loan_amount) }}</td>
                    <td>{{ \App\Support\Format::money($application->emi) }}</td>
                    <td><x-status-badge :status="$application->status" :label="$application->applicationStatus?->name" /></td>
                    <td>{{ $application->assignee?->name ?? '—' }}</td>
                    <td class="text-muted text-nowrap">{{ $application->created_at?->format('d M Y') }}</td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('applications.show', $application) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                            <button type="button" class="btn btn-sm btn-light" title="Quick view"
                                    data-application-quick-view="{{ route('applications.quick-view', $application) }}">
                                <i class="bi bi-lightning-charge"></i>
                            </button>
                            @can('update', $application)
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#status-application-modal"
                                                data-status-url="{{ route('applications.status', $application) }}">
                                            <i class="bi bi-arrow-repeat me-2"></i>Change status
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#assign-application-modal"
                                                data-assign-url="{{ route('applications.assign', $application) }}">
                                            <i class="bi bi-person-plus me-2"></i>Assign
                                        </button>
                                    </li>
                                    @canPermission('disbursements.create')
                                        <li><a class="dropdown-item" href="{{ route('disbursements.create', ['loan_application_id' => $application->id]) }}"><i class="bi bi-cash-stack me-2"></i>Start disbursement</a></li>
                                    @endcanPermission
                                </ul>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <x-empty-state icon="bi-clipboard-data" title="No applications found" message="Convert a verified lead into an application to get started.">
                            <x-slot:action>
                                @can('create', App\Models\LoanApplication::class)
                                    <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New application</a>
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
