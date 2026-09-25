@extends('layouts.app')

@section('title', 'Employee Management')
@section('page-header', true)
@section('page-title', 'Employee Management')
@section('page-subtitle', 'Team members, roles, permissions and productivity in one place.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Employees</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('export', App\Models\Employee::class)
            <a href="{{ route('employees.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
        @endcan
        @canPermission('employees.manage')
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add Employee</a>
        @endcanPermission
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Total employees" :value="number_format($stats['total'])" icon="bi-people" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Active" :value="number_format($stats['active'])" icon="bi-person-check" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Present today" :value="number_format($stats['present_today'])" icon="bi-calendar-check" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="On leave" :value="number_format($stats['on_leave'] ?? 0)" icon="bi-airplane" tone="orange" /></div>
    </div>

    <x-filter-panel :action="route('employees.index')" :reset-url="route('employees.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Name, employee code, mobile or email" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="department_id">Department</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) ($filters['department_id'] ?? 0) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="designation_id">Designation</label>
                <select class="form-select" id="designation_id" name="designation_id">
                    <option value="">All designations</option>
                    @foreach ($designations as $designation)
                        <option value="{{ $designation->id }}" @selected((int) ($filters['designation_id'] ?? 0) === $designation->id)>{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="employment_status">Status</label>
                <select class="form-select" id="employment_status" name="employment_status">
                    <option value="">All</option>
                    @foreach (['active' => 'Active', 'probation' => 'Probation', 'resigned' => 'Resigned', 'terminated' => 'Terminated'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['employment_status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="per_page">Rows</label>
                <select class="form-select" id="per_page" name="per_page">
                    @foreach (config('loanpro.pagination.options', [10, 25, 50, 100]) as $option)
                        <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filter-panel>

    <x-card :padding="false">
        <div id="employee-table-body">
            @include('employees.partials.table', ['items' => $employees])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('employees.index') }}', container: '#employee-table-body' });
</script>
@endpush
