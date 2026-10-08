@extends('layouts.app')

@php $isEdit = $payout->exists; @endphp

@section('title', $isEdit ? 'Edit payment' : 'Add payment')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit payment '.$payout->payout_code : 'Add employee payment')
@section('page-subtitle', 'Approved leads and coins are calculated automatically for the selected month.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Add' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $isEdit ? route('payments.update', $payout) : route('payments.store') }}" id="payout-form">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-section title="Employee & month" icon="bi-person-badge" description="Pick the employee and the month the payout is for.">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="employee_id">Employee<span class="req">*</span></label>
                    <select class="form-select" id="employee_id" name="employee_id" required data-employee>
                        <option value="">Select employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id', $payout->employee_id) == $employee->id)>
                                {{ $employee->user?->name ?? $employee->employee_code }} · {{ $employee->employee_code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="payout_month">Month<span class="req">*</span></label>
                    <input type="month" class="form-control" id="payout_month" name="payout_month" required
                           value="{{ $payout->payout_month?->format('Y-m') ?? now()->format('Y-m') }}" data-month>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <div class="text-muted small" data-figures-hint>Choose an employee to load the approved leads for the month.</div>
                </div>
            </div>
        </x-section>

        <x-section title="Amount" icon="bi-calculator" description="Salary and coins come from the employee record and approved leads; enter the incentive.">
            <div class="row g-3">
                <div class="col-md-2"><label class="form-label">Total leads (approved)</label><input class="form-control" value="{{ $payout->approved_leads }}" readonly data-approved-leads></div>
                <div class="col-md-2"><label class="form-label">Coins per lead</label><input class="form-control" value="{{ number_format((float) $payout->coins_per_lead, 2, '.', '') }}" readonly data-coins-per-lead></div>
                <div class="col-md-2"><label class="form-label">Total coins</label><input class="form-control" value="{{ number_format((float) $payout->total_coins, 2, '.', '') }}" readonly data-total-coins></div>
                <div class="col-md-3"><label class="form-label">Monthly salary</label><input class="form-control" value="{{ number_format((float) $payout->monthly_salary, 2, '.', '') }}" readonly data-salary></div>
                <div class="col-md-3">
                    <label class="form-label" for="incentive_amount">Incentive (admin amount)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="incentive_amount" name="incentive_amount"
                           value="{{ old('incentive_amount', (float) $payout->incentive_amount) }}" data-incentive>
                </div>
                <div class="col-md-3"><label class="form-label">Total amount</label><input class="form-control fw-semibold" readonly data-total value="{{ number_format((float) $payout->total_amount, 2, '.', '') }}"></div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        @foreach (\App\Models\EmployeePayout::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $payout->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="paid_on">Paid on</label>
                    <input type="date" class="form-control" id="paid_on" name="paid_on" value="{{ old('paid_on', $payout->paid_on?->format('Y-m-d')) }}">
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $payout->notes) }}</textarea>
                </div>
            </div>
        </x-section>

        <div class="d-flex justify-content-between">
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary">Save payment</button>
        </div>
    </form>
@endsection

@push('scripts')
<script type="module">
    const form = document.getElementById('payout-form');
    const employee = form.querySelector('[data-employee]');
    const month = form.querySelector('[data-month]');
    const incentive = form.querySelector('[data-incentive]');
    const total = form.querySelector('[data-total]');
    const money = (value) => (Number(value) || 0).toFixed(2);

    const recalc = () => {
        const salary = Number(form.querySelector('[data-salary]').value) || 0;
        total.value = money(salary + (Number(incentive.value) || 0));
    };

    async function loadFigures() {
        if (!employee.value || !month.value) return;

        const query = new URLSearchParams({ employee_id: employee.value, payout_month: month.value });
        const payload = await LoanPro.request(`{{ route('payments.summary') }}?${query}`);
        const f = payload.figures;

        form.querySelector('[data-approved-leads]').value = f.approved_leads;
        form.querySelector('[data-coins-per-lead]').value = money(f.coins_per_lead);
        form.querySelector('[data-total-coins]').value = money(f.total_coins);
        form.querySelector('[data-salary]').value = money(f.monthly_salary);
        form.querySelector('[data-figures-hint]').textContent =
            `${f.approved_leads} approved lead(s) in ${month.value}.`;
        recalc();
    }

    employee.addEventListener('change', loadFigures);
    month.addEventListener('change', loadFigures);
    incentive.addEventListener('input', recalc);
    recalc();
</script>
@endpush
