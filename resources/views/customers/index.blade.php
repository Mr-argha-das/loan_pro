@extends('layouts.app')

@section('title', 'Customer Management')
@section('page-header', true)
@section('page-title', 'Customer Management')
@section('page-subtitle', 'Every customer profile, KYC state and relationship owner in one place.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Customers</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('export', App\Models\Customer::class)
            <a href="{{ route('customers.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
        @endcan
        @can('create', App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add Customer</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Total customers" :value="number_format($stats['total'])" icon="bi-people" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="KYC verified" :value="number_format($stats['verified'])" icon="bi-patch-check" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="KYC pending" :value="number_format($stats['pending'])" icon="bi-hourglass-split" tone="orange" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Added this month" :value="number_format($stats['new_this_month'])" icon="bi-graph-up-arrow" tone="blue" /></div>
    </div>

    <x-filter-panel :action="route('customers.index')" :reset-url="route('customers.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Name, mobile, email or customer ID" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="kyc_status">KYC status</label>
                <select class="form-select" id="kyc_status" name="kyc_status">
                    <option value="">All</option>
                    @foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['kyc_status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="city">City</label>
                <select class="form-select" id="city" name="city">
                    <option value="">All cities</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(($filters['city'] ?? null) === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="assigned_employee_id">Owner</label>
                <select class="form-select" id="assigned_employee_id" name="assigned_employee_id">
                    <option value="">Everyone</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((int) ($filters['assigned_employee_id'] ?? 0) === $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="per_page">Rows</label>
                <select class="form-select" id="per_page" name="per_page">
                    @foreach (config('loanpro.pagination.options', [10, 25, 50, 100]) as $option)
                        <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }} per page</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filter-panel>

    <x-card :padding="false">
        <div id="customer-table-body">
            @include('customers.partials.table', ['items' => $customers])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({
        url: '{{ route('customers.index') }}',
        container: '#customer-table-body',
        afterReload: () => LoanPro.bindRowActions?.(),
    });
</script>
@endpush
