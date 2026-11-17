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
<style>
    .lp-stat {
    min-width: 0;
     background: linear-gradient(135deg, #1689ed, #0871e8)!important;
    border: 1px solid var(--lp-border);
    border-radius: var(--lp-radius);
    padding: 18px;
    height: 100%;
    box-shadow: var(--lp-shadow-xs);
    transition: box-shadow var(--lp-transition), transform var(--lp-transition);
}

/* Gradients */
.card-blue {
    background: linear-gradient(135deg, #1689ed, #0871e8)!important;
}

.card-green {
    background: linear-gradient(135deg, #35ca72, #0cae5c)!important;
}

.card-purple {
    background: linear-gradient(135deg, #9550ed, #7b35df)!important;
}

.card-orange {
    background: linear-gradient(135deg, #ffc536, #ff9e19)!important;
}

.card-red {
    background: linear-gradient(135deg, #fa3d4e, #ed1c35)!important;
}

.card-cyan {
    background: linear-gradient(135deg, #16c7c6, #08a9bd)!important;
}

.card-blue-dark {
    background: linear-gradient(135deg, #4778e8, #2459ca)!important;
}

.card-pink {
    background: linear-gradient(135deg, #ed4c9c, #dd2180)!important;
}

.lp-stat__label{
    color: #fff!important;
}

.lp-stat__value{
   color: #fff!important; 
}

.lp-stat__meta{
     color: #fff!important; 
}

.lp-stat__meta span.fw-semibold.text-success{
    color: #fff!important; 
}

/* Left Icon */
.lp-stat__icon {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 11px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 23px;
    font-weight: 400;

    background: rgba(255, 255, 255, 0.18)!important;
    border: 1px solid rgba(255, 255, 255, 0.08)!important;
    color: #fff!important;
    box-shadow: inset 0 1px 5px rgba(255, 255, 255, 0.12)!important;
}
    
</style>
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
        <div class="col-6 col-xl-2"><x-stat-card label="Employees" :value="$totals['employees']" icon="bi-people" tone="primary" meta="in {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}" class="card-blue" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Approved leads" :value="$totals['leads']" icon="bi-patch-check" tone="info" class="card-green" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Total coins" :value="number_format($totals['coins'], 2)" icon="bi-coin" tone="warning" class="card-purple" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Total payable" :value="\App\Support\Format::money($totals['payable'])" icon="bi-wallet2" tone="primary" class="card-orange" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Paid" :value="\App\Support\Format::money($totals['paid'])" icon="bi-check2-circle" tone="success" class="card-red" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Unpaid" :value="\App\Support\Format::money($totals['unpaid'])" icon="bi-exclamation-circle" tone="danger" class="card-cyan" /></div>
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
