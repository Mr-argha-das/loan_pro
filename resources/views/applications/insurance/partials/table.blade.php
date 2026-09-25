@php $items = $items ?? ($applications ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Application</th>
                <th scope="col">Customer</th>
                <th scope="col">Type / Plan</th>
                <th scope="col">Insurer</th>
                <th scope="col">Sum assured</th>
                <th scope="col">Premium</th>
                <th scope="col">Status</th>
                <th scope="col">Created</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $application)
                <tr>
                    <td>
                        <a href="{{ route('insurance.show', $application) }}" class="fw-semibold">{{ $application->application_code }}</a>
                        @if ($application->policy_number)
                            <div class="text-muted" style="font-size:.73rem">Policy {{ $application->policy_number }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $application->customer?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $application->customer?->mobile }}</div>
                    </td>
                    <td>
                        <div>{{ $application->category?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $application->plan?->name ?? $application->subcategory?->name }}</div>
                    </td>
                    <td>{{ $application->insurer?->name ?? '—' }}</td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($application->sum_assured) }}</td>
                    <td>
                        {{ \App\Support\Format::money($application->premium_amount) }}
                        <div class="text-muted" style="font-size:.73rem">{{ \App\Support\Format::titleCase($application->premium_frequency) }}</div>
                    </td>
                    <td><x-status-badge :status="$application->status" /></td>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($application->created_at) }}</td>
                    <td class="text-end">
                        <a href="{{ route('insurance.show', $application) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <x-empty-state icon="bi-shield-check" title="No insurance applications" message="Life, health and general policies raised for customers appear here.">
                            <x-slot:action>
                                @can('create', App\Models\LoanApplication::class)
                                    <a href="{{ route('insurance.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New application</a>
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
