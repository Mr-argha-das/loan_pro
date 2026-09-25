<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasAnyPermission(['attendance.view', 'attendance.mark']), 403);

        $user = $request->user();
        $month = $request->string('month', now()->format('Y-m'))->toString();

        $query = Attendance::query()
            ->with(['employee.user', 'employee.department'])
            ->whereBetween('attendance_date', [
                \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth(),
                \Illuminate\Support\Carbon::parse($month.'-01')->endOfMonth(),
            ])
            ->when(! $user->hasPermissionTo('attendance.view'), fn ($q) => $q->where('employee_id', $user->employee?->id))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->orderByDesc('attendance_date');

        $records = $query->paginate($this->perPage($request))->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($records, 'attendance.partials.table');
        }

        $today = Attendance::query()
            ->where('employee_id', $user->employee?->id)
            ->whereDate('attendance_date', today())
            ->first();

        return view('attendance.index', [
            'records' => $records,
            'month' => $month,
            'today' => $today,
            'employees' => Employee::query()->with('user')->get(),
            'departments' => Department::query()->active()->ordered()->get(),
            'summary' => $this->summary($user, $month),
            'leaves' => Leave::query()
                ->with(['employee.user'])
                ->when(! $user->hasPermissionTo('attendance.view'), fn ($q) => $q->where('employee_id', $user->employee?->id))
                ->latest()
                ->limit(10)
                ->get(),
            'filters' => $request->all(),
        ]);
    }

    protected function summary($user, string $month): array
    {
        $base = fn () => Attendance::query()
            ->when(! $user->hasPermissionTo('attendance.view'), fn ($q) => $q->where('employee_id', $user->employee?->id))
            ->whereBetween('attendance_date', [
                \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth(),
                \Illuminate\Support\Carbon::parse($month.'-01')->endOfMonth(),
            ]);

        return [
            'present' => $base()->where('status', 'present')->count(),
            'late' => $base()->where('status', 'late')->count(),
            'absent' => $base()->where('status', 'absent')->count(),
            'leave' => $base()->where('status', 'leave')->count(),
            'half_day' => $base()->where('status', 'half_day')->count(),
            'hours' => round($base()->sum('worked_minutes') / 60, 1),
        ];
    }

    public function checkIn(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($employee, 403, 'Your account is not linked to an employee profile.');

        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        if ($existing?->check_in_at) {
            return $request->expectsJson()
                ? $this->fail('You have already checked in today.', 422)
                : back()->with('error', 'You have already checked in today.');
        }

        $grace = (int) config('loanpro.attendance.late_grace_minutes', 15);
        $officeStart = \App\Models\Setting::get('office_start_time', '09:30');
        $expected = \Illuminate\Support\Carbon::parse(today()->toDateString().' '.$officeStart);
        $lateMinutes = max(0, (int) $expected->diffInMinutes(now(), false));

        $attendance = Attendance::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'attendance_date' => today()->toDateString()],
            [
                'check_in_at' => now(),
                'status' => $lateMinutes > $grace ? 'late' : 'present',
                'late_minutes' => $lateMinutes > $grace ? $lateMinutes : 0,
                'work_mode' => $request->input('work_mode', 'office'),
            ]
        );

        if ($request->expectsJson()) {
            return $this->ok('Checked in at '.now()->format('h:i A').'.', ['status' => $attendance->status]);
        }

        return back()->with('success', 'Checked in at '.now()->format('h:i A').'.');
    }

    public function checkOut(Request $request): RedirectResponse|JsonResponse
    {
        $employee = $request->user()->employee;

        abort_unless($employee, 403, 'Your account is not linked to an employee profile.');

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        if (! $attendance?->check_in_at) {
            return $request->expectsJson()
                ? $this->fail('Please check in before checking out.', 422)
                : back()->with('error', 'Please check in before checking out.');
        }

        if ($attendance->check_out_at) {
            return $request->expectsJson()
                ? $this->fail('You have already checked out today.', 422)
                : back()->with('error', 'You have already checked out today.');
        }

        $worked = (int) $attendance->check_in_at->diffInMinutes(now());
        $halfDayMinutes = (int) config('loanpro.attendance.half_day_minutes', 300);

        $attendance->forceFill([
            'check_out_at' => now(),
            'worked_minutes' => $worked,
            'status' => $worked < $halfDayMinutes ? 'half_day' : $attendance->status,
        ])->save();

        if ($request->expectsJson()) {
            return $this->ok('Checked out at '.now()->format('h:i A').'. Worked '.round($worked / 60, 1).' hours.');
        }

        return back()->with('success', 'Checked out at '.now()->format('h:i A').'.');
    }

    public function mark(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('attendance.manage'), 403);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,half_day,leave,holiday,week_off'],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after:check_in_at'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        Attendance::query()->updateOrCreate(
            ['employee_id' => $data['employee_id'], 'attendance_date' => $data['attendance_date']],
            array_merge($data, [
                'worked_minutes' => ($data['check_in_at'] ?? null) && ($data['check_out_at'] ?? null)
                    ? \Illuminate\Support\Carbon::parse($data['check_in_at'])->diffInMinutes(\Illuminate\Support\Carbon::parse($data['check_out_at']))
                    : 0,
            ])
        );

        return back()->with('success', 'Attendance updated.');
    }

    public function requestLeave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'leave_type' => ['required', 'in:casual,sick,earned,unpaid'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $employee = $request->user()->employee;

        abort_unless($employee, 403, 'Your account is not linked to an employee profile.');

        Leave::query()->create(array_merge($data, [
            'employee_id' => $employee->id,
            'days' => \Illuminate\Support\Carbon::parse($data['from_date'])->diffInDays(\Illuminate\Support\Carbon::parse($data['to_date'])) + 1,
            'status' => 'pending',
        ]));

        return back()->with('success', 'Leave request submitted for approval.');
    }

    public function decideLeave(Request $request, Leave $leave): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('attendance.manage'), 403);

        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);

        $leave->forceFill([
            'status' => $data['status'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();

        return back()->with('success', 'Leave request '.$data['status'].'.');
    }

    public function export(Request $request, ExportService $export)
    {
        abort_unless($request->user()->hasPermissionTo('attendance.view'), 403);

        $month = $request->string('month', now()->format('Y-m'))->toString();

        $rows = Attendance::query()
            ->with(['employee.user', 'employee.department'])
            ->whereBetween('attendance_date', [
                \Illuminate\Support\Carbon::parse($month.'-01')->startOfMonth(),
                \Illuminate\Support\Carbon::parse($month.'-01')->endOfMonth(),
            ])
            ->orderBy('attendance_date')
            ->limit(5000)
            ->get()
            ->map(fn (Attendance $a) => [
                $a->attendance_date?->format('d M Y'),
                $a->employee?->user?->name,
                $a->employee?->employee_code,
                $a->employee?->department?->name,
                $a->check_in_at?->format('h:i A'),
                $a->check_out_at?->format('h:i A'),
                round($a->worked_minutes / 60, 1),
                $a->status,
            ]);

        return $export->xlsx('attendance-'.$month.'.xlsx', [
            'Date', 'Employee', 'Code', 'Department', 'Check In', 'Check Out', 'Hours', 'Status',
        ], $rows, 'Attendance', [
            'Report' => 'Attendance Register - '.$month,
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }
}
