<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePayoutRequest;
use App\Models\Employee;
use App\Models\EmployeePayout;
use App\Services\EmployeePayoutService;
use App\Services\CodeGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Payments module = monthly employee payouts (salary + incentive per approved lead).
 */
class PaymentController extends Controller
{
    public function __construct(
        protected EmployeePayoutService $figures,
        protected CodeGeneratorService $codes,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('payments.view'), 403);

        $month = $this->month($request);

        $payouts = EmployeePayout::query()
            ->with('employee.user')
            ->forMonth($month)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('employee.user', fn ($u) => $u->where('name', 'like', '%'.$request->string('q').'%')))
            ->orderBy('id')
            ->get();

        return view('finance.payments.index', [
            'month' => $month,
            'payouts' => $payouts,
            'totals' => [
                'employees' => $payouts->count(),
                'leads' => $payouts->sum('approved_leads'),
                'coins' => (float) $payouts->sum('total_coins'),
                'payable' => (float) $payouts->sum('total_amount'),
                'paid' => (float) $payouts->where('status', 'paid')->sum('total_amount'),
                'unpaid' => (float) $payouts->where('status', 'unpaid')->sum('total_amount'),
            ],
            'employees' => Employee::query()->with('user')->where('employment_status', 'active')->get(),
            'filters' => $request->all(),
        ]);
    }

    /** Figures for one employee and month (used by the create form). */
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('payments.view'), 403);

        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'payout_month' => ['required', 'date_format:Y-m'],
        ]);

        $employee = Employee::query()->findOrFail($request->integer('employee_id'));
        $figures = $this->figures->figures($employee, $request->string('payout_month')->toString());

        return response()->json(['success' => true, 'figures' => $figures]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('payments.manage'), 403);

        return view('finance.payments.form', [
            'payout' => new EmployeePayout(['payout_month' => now()->startOfMonth(), 'status' => 'unpaid']),
            'employees' => Employee::query()->with('user')->where('employment_status', 'active')->get(),
        ]);
    }

    public function store(StorePayoutRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id']);

        $exists = EmployeePayout::query()
            ->where('employee_id', $employee->id)
            ->forMonth($data['payout_month'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'employee_id' => 'A payout for this employee already exists for that month. Edit it instead.',
            ]);
        }

        $payout = DB::transaction(fn () => EmployeePayout::query()->create(
            $this->payload($employee, $data) + [
                'payout_code' => $this->codes->payout(),
                'created_by' => $request->user()->id,
            ]
        ));

        return redirect()->route('payments.index', ['month' => $data['payout_month']])
            ->with('success', 'Payout '.$payout->payout_code.' saved.');
    }

    public function edit(Request $request, EmployeePayout $payment): View
    {
        abort_unless($request->user()->hasPermissionTo('payments.manage'), 403);

        return view('finance.payments.form', [
            'payout' => $payment->load('employee.user'),
            'employees' => Employee::query()->with('user')->get(),
        ]);
    }

    public function update(StorePayoutRequest $request, EmployeePayout $payment): RedirectResponse
    {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id']);

        $payment->update($this->payload($employee, $data));

        return redirect()->route('payments.index', ['month' => $data['payout_month']])
            ->with('success', 'Payout '.$payment->payout_code.' updated.');
    }

    public function destroy(Request $request, EmployeePayout $payment): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('payments.manage'), 403);

        $month = $payment->payout_month->format('Y-m');
        $payment->delete();

        return redirect()->route('payments.index', ['month' => $month])->with('success', 'Payout deleted.');
    }

    /* ------------------------------------------------------------------ helpers */

    protected function month(Request $request): string
    {
        $value = $request->string('month')->toString();

        return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : now()->format('Y-m');
    }

    /** Figures are recomputed on the server so the stored payout can never drift from the data. */
    protected function payload(Employee $employee, array $data): array
    {
        $figures = $this->figures->figures($employee, $data['payout_month']);
        $incentive = (float) ($data['incentive_amount'] ?? 0);

        return [
            'employee_id' => $employee->id,
            'payout_month' => Carbon::createFromFormat('Y-m-d', $data['payout_month'].'-01')->toDateString(),
            'approved_leads' => $figures['approved_leads'],
            'coins_per_lead' => $figures['coins_per_lead'],
            'total_coins' => $figures['total_coins'],
            'monthly_salary' => $figures['monthly_salary'],
            'incentive_amount' => $incentive,
            'total_amount' => round($figures['monthly_salary'] + $incentive, 2),
            'status' => $data['status'],
            'paid_on' => $data['status'] === 'paid' ? ($data['paid_on'] ?? now()->toDateString()) : null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
