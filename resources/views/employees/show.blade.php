@extends('layouts.app')

@section('title', $employee->user?->name ?? 'Employee')
@section('page-header', true)
@section('page-title', $employee->user?->name)
@section('page-subtitle', ($employee->designation?->name ?? 'Employee').' · '.($employee->department?->name ?? 'No department').' · '.$employee->employee_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $employee->employee_code }}</li>
@endsection

@section('page-actions')
    @canPermission('employees.manage')
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    @endcanPermission
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <x-card title="Profile" icon="bi-person-badge">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="lp-avatar lp-avatar--xl">{{ $employee->user?->initials() }}</span>
                    <div>
                        <div class="fw-semibold">{{ $employee->user?->name }}</div>
                        <div class="text-muted small">{{ $employee->designation?->name ?? '—' }}</div>
                        <div class="mt-1"><x-status-badge :status="$employee->employment_status" /></div>
                    </div>
                </div>

                <div class="lp-kv"><span class="lp-kv__label">Employee code</span><span class="lp-kv__value">{{ $employee->employee_code }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Email</span><span class="lp-kv__value">{{ $employee->user?->email }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Mobile</span><span class="lp-kv__value">{{ $employee->mobile ?? $employee->user?->phone ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Department</span><span class="lp-kv__value">{{ $employee->department?->name ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Role</span><span class="lp-kv__value">{{ $employee->user?->role?->name ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Joined</span><span class="lp-kv__value">{{ \App\Support\Format::date($employee->joining_date) }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Monthly target</span><span class="lp-kv__value">{{ \App\Support\Format::money($employee->monthly_target) }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Bank</span><span class="lp-kv__value">{{ $employee->bank_name ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Emergency contact</span><span class="lp-kv__value">{{ $employee->emergency_contact_name ?? '—' }} {{ $employee->emergency_contact_number ? '· '.$employee->emergency_contact_number : '' }}</span></div>
            </x-card>

            <x-card title="Extra permissions" icon="bi-shield-lock" class="mt-4">
                @forelse ($employee->user?->extraPermissions ?? collect() as $permission)
                    <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10 mb-1">{{ $permission->name }}</span>
                @empty
                    <p class="text-muted small mb-0">This employee inherits permissions from their role only.</p>
                @endforelse
            </x-card>
        </div>

        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3"><x-stat-card label="Assigned leads" :value="number_format($performance['leads'])" icon="bi-funnel" tone="primary" /></div>
                <div class="col-6 col-lg-3"><x-stat-card label="Converted" :value="number_format($performance['converted'])" icon="bi-check2-circle" tone="green" /></div>
                <div class="col-6 col-lg-3"><x-stat-card label="Applications" :value="number_format($performance['applications'])" icon="bi-clipboard-data" tone="blue" /></div>
                <div class="col-6 col-lg-3"><x-stat-card label="Disbursed value" :value="\App\Support\Format::compactInr($performance['disbursed_value'])" icon="bi-cash-stack" tone="orange" /></div>
            </div>

            <x-card title="Recent leads" icon="bi-funnel" :padding="false">
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Lead</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLeads as $lead)
                                <tr>
                                    <td><a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a></td>
                                    <td>{{ $lead->customer?->name }}</td>
                                    <td class="fw-semibold">{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                                    <td><x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" /></td>
                                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($lead->created_at) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty-state icon="bi-funnel" title="No leads assigned" message="Leads assigned to this employee will appear here." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Recent attendance" icon="bi-calendar-check" class="mt-4" :padding="false">
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Check in</th>
                                <th scope="col">Check out</th>
                                <th scope="col">Hours</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendance as $record)
                                <tr>
                                    <td class="fw-semibold">{{ \App\Support\Format::date($record->attendance_date) }}</td>
                                    <td>{{ \App\Support\Format::time($record->check_in_at) }}</td>
                                    <td>{{ \App\Support\Format::time($record->check_out_at) }}</td>
                                    <td>{{ $record->workedHours() ?? '—' }}</td>
                                    <td><x-status-badge :status="$record->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty-state icon="bi-calendar-x" title="No attendance yet" message="Daily check-ins will be listed here." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection
