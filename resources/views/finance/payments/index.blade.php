@extends('layouts.app')

@section('title', 'Payments')
@section('page-header', true)
@section('page-title', 'Employee Payments')
@section('page-subtitle', 'Monthly salary and incentive per employee, based on approved leads.')

@section('page-actions')
    @canPermission('payments.manage')
        <a href="{{ route('payments.create', ['month' => $month]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add payment</a>
    @endcanPermission
@endsection

@section('content')
    <form method="GET" action="{{ route('payments.index') }}" class="lp-card mb-4">
        <div class="lp-card__body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="month">Month</label>
                    <input type="month" class="form-control" id="month" name="month" value="{{ $month }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\EmployeePayout::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="q">Employee</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search employee name">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100">Apply</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2"><x-stat-card label="Employees" :value="$totals['employees']" icon="bi-people" tone="primary" meta="in {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Approved leads" :value="$totals['leads']" icon="bi-patch-check" tone="info" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Total coins" :value="number_format($totals['coins'], 2)" icon="bi-coin" tone="warning" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Total payable" :value="\App\Support\Format::money($totals['payable'])" icon="bi-wallet2" tone="primary" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Paid" :value="\App\Support\Format::money($totals['paid'])" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Unpaid" :value="\App\Support\Format::money($totals['unpaid'])" icon="bi-exclamation-circle" tone="danger" /></div>
    </div>

    <x-card title="Payments" icon="bi-wallet2" :subtitle="'Figures for '.\Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y')" padding="false">
        <div class="table-responsive">
            <table class="lp-table">
                <thead>
                    <tr>
                        <th scope="col">Employee</th>
                        <th scope="col">Total leads</th>
                        <th scope="col">Total coins</th>
                        <th scope="col">Monthly salary</th>
                        <th scope="col">Incentive</th>
                        <th scope="col">Total amount</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $payout->employee?->user?->name ?? 'Employee' }}</div>
                                <div class="text-muted" style="font-size:.73rem">{{ $payout->payout_code }}</div>
                            </td>
                            <td>{{ $payout->approved_leads }}</td>
                            <td>{{ number_format((float) $payout->total_coins, 2) }}<div class="text-muted" style="font-size:.73rem">{{ number_format((float) $payout->coins_per_lead, 2) }} per lead</div></td>
                            <td>{{ \App\Support\Format::money($payout->monthly_salary) }}</td>
                            <td>{{ \App\Support\Format::money($payout->incentive_amount) }}</td>
                            <td class="fw-semibold">{{ \App\Support\Format::money($payout->total_amount) }}</td>
                            <td>
                                <span class="lp-badge bg-{{ $payout->isPaid() ? 'success' : 'warning' }}-subtle text-{{ $payout->isPaid() ? 'success' : 'warning' }} bg-opacity-10">
                                    {{ \App\Models\EmployeePayout::STATUSES[$payout->status] ?? $payout->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @canPermission('payments.manage')
                                    <a href="{{ route('payments.edit', $payout) }}" class="btn btn-sm btn-light" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('payments.destroy', $payout) }}" class="d-inline"
                                          data-confirm="Delete payout {{ $payout->payout_code }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endcanPermission
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state icon="bi-wallet2" title="No payouts for this month" message="Add a payment to record an employee's salary and incentive." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
