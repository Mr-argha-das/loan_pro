@extends('layouts.app')

@section('title', 'Attendance')
@section('page-header', true)
@section('page-title', 'Attendance')
@section('page-subtitle', 'Daily check-in/out, late marks, work mode and leave requests.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Attendance</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @unless (auth()->user()->isAdmin())
        @if (! $today)
            <form method="POST" action="{{ route('attendance.check-in') }}" data-geo-form>
                @csrf
                <button class="btn btn-success btn-sm"><i class="bi bi-box-arrow-in-right"></i> Check in</button>
            </form>
        @elseif (! $today->check_out_at)
            <form method="POST" action="{{ route('attendance.check-out') }}" data-geo-form>
                @csrf
                <button class="btn btn-primary btn-sm"><i class="bi bi-box-arrow-right"></i> Check out</button>
            </form>
        @else
            <span class="lp-badge bg-success-subtle text-success bg-opacity-10 align-self-center">
                <i class="bi bi-check2-circle"></i> Day complete · {{ $today->workedHours() ?? '—' }}
            </span>
        @endif
        @endunless
        <a href="{{ route('attendance.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
    </div>
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
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Present days" :value="number_format($summary['present'] ?? 0)" icon="bi-calendar-check" tone="green" class="card-blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Late marks" :value="number_format($summary['late'] ?? 0)" icon="bi-clock-history" tone="orange" class="card-green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Absent" :value="number_format($summary['absent'] ?? 0)" icon="bi-calendar-x" tone="red" class="card-purple" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Avg hours / day" :value="number_format($summary['average_hours'] ?? 0, 1).' h'" icon="bi-hourglass-split" tone="primary" class="card-orange" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card :padding="false">
                <div class="lp-card__header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="lp-tone-primary rounded-3 d-grid" style="width:34px;height:34px;place-items:center"><i class="bi bi-calendar3"></i></span>
                        <div>
                            <h2 class="lp-card__title">Attendance register</h2>
                            <div class="text-muted" style="font-size:.78rem">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-3 pb-0">
                    <form method="GET" action="{{ route('attendance.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label" for="month">Month</label><input type="month" class="form-control" id="month" name="month" value="{{ $month }}"></div>
                        @if (auth()->user()->isAdmin() || auth()->user()->hasPermissionTo('attendance.manage'))
                            <div class="col-md-3">
                                <label class="form-label" for="employee_id">Employee</label>
                                <select class="form-select" id="employee_id" name="employee_id">
                                    <option value="">Everyone</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected((int) request('employee_id') === $employee->id)>{{ $employee->user?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="department_id">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">All departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((int) request('department_id') === $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </form>
                </div>

                <div id="attendance-table-body" class="mt-3">
                    @include('attendance.partials.table', ['items' => $records])
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Leave requests" icon="bi-airplane">
                <x-slot:actions>
                    @canPermission('attendance.requests')
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#leave-modal">
                            <i class="bi bi-plus-lg"></i> Request
                        </button>
                    @endcanPermission
                </x-slot:actions>

                @forelse ($leaves as $leave)
                    <div class="lp-kv">
                        <span class="lp-kv__label">
                            {{ $leave->employee?->user?->name }}<br>
                            <span style="font-size:.72rem">{{ \App\Support\Format::date($leave->from_date) }} → {{ \App\Support\Format::date($leave->to_date) }}</span>
                        </span>
                        <span class="lp-kv__value"><x-status-badge :status="$leave->status" /></span>
                    </div>
                @empty
                    <x-empty-state icon="bi-airplane" title="No leave requests" message="Applied leaves and their approvals show up here." />
                @endforelse
            </x-card>
        </div>
    </div>

    @canPermission('attendance.requests')
        <x-modal id="leave-modal" title="Request leave" :action="route('attendance.leave.request')" submit-label="Submit request">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="leave_employee_id">Employee<span class="req">*</span></label>
                    <select class="form-select" id="leave_employee_id" name="employee_id" required>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($employee->user_id === auth()->id())>{{ $employee->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-select name="leave_type" label="Leave type" required
                              :options="['casual' => 'Casual', 'sick' => 'Sick', 'earned' => 'Earned', 'unpaid' => 'Unpaid', 'compensatory' => 'Compensatory']" />
                </div>
                <div class="col-md-6"><x-input name="from_date" label="From" type="date" required :value="now()->toDateString()" /></div>
                <div class="col-md-6"><x-input name="to_date" label="To" type="date" required :value="now()->toDateString()" /></div>
                <div class="col-12"><x-textarea name="reason" label="Reason" rows="2" required /></div>
            </div>
        </x-modal>
    @endcanPermission
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('attendance.index') }}', container: '#attendance-table-body', form: 'form[method="GET"]' });
</script>
@endpush
