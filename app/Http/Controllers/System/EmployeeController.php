<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Disbursement;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CodeGeneratorService;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(protected CodeGeneratorService $codes)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('employees.view'), 403);

        $employees = Employee::query()
            ->with(['user.role', 'department', 'designation'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(fn ($sub) => $sub->where('employee_code', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")));
            })
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('designation_id'), fn ($q) => $q->where('designation_id', $request->integer('designation_id')))
            ->when($request->filled('employment_status'), fn ($q) => $q->where('employment_status', $request->string('employment_status')))
            ->orderBy('employee_code')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($employees, 'employees.partials.table');
        }

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::query()->active()->ordered()->get(),
            'designations' => Designation::query()->active()->ordered()->get(),
            'roles' => Role::query()->where('is_active', true)->orderBy('level')->get(),
            'stats' => [
                'total' => Employee::query()->count(),
                'active' => Employee::query()->where('employment_status', 'active')->count(),
                'present_today' => Attendance::query()->whereDate('attendance_date', today())->whereIn('status', ['present', 'late'])->count(),
                'on_leave' => Attendance::query()->whereDate('attendance_date', today())->where('status', 'leave')->count(),
            ],
            'filters' => $request->all(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('employees.manage'), 403);

        return view('employees.form', [
            'employee' => new Employee(['employment_status' => 'active', 'joining_date' => now()->toDateString()]),
            'user' => new User(['status' => 'active']),
            'departments' => Department::query()->active()->ordered()->get(),
            'designations' => Designation::query()->active()->ordered()->get(),
            'roles' => Role::query()->where('is_active', true)->orderBy('level')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $employee = DB::transaction(function () use ($data, $request) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role_id' => $data['role_id'],
                'designation' => $data['designation'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'password' => Hash::make($data['password'] ?? 'password'),
            ]);

            if (! empty($data['permissions'])) {
                $user->extraPermissions()->sync($data['permissions']);
            }

            return Employee::query()->create([
                'user_id' => $user->id,
                'employee_code' => $data['employee_code'] ?? $this->codes->employee(),
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'joining_date' => $data['joining_date'] ?? now()->toDateString(),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'employment_status' => $data['employment_status'] ?? 'active',
                'monthly_target' => $data['monthly_target'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_ifsc' => $data['bank_ifsc'] ?? null,
            ]);
        });

        return redirect()->route('employees.show', $employee)->with('success', 'Employee '.$employee->user->name.' created.');
    }

    public function show(Employee $employee): View
    {
        abort_unless(auth()->user()->hasPermissionTo('employees.view') || $employee->user_id === auth()->id(), 403);

        return view('employees.show', [
            'employee' => $employee->load(['user.role', 'user.extraPermissions', 'department', 'designation']),
            'performance' => [
                'leads' => Lead::query()->where('assigned_to', $employee->user_id)->count(),
                'converted' => Lead::query()->where('assigned_to', $employee->user_id)->where('is_converted', true)->count(),
                'applications' => LoanApplication::query()->where('assigned_to', $employee->user_id)->count(),
                'disbursed_value' => (float) Disbursement::query()->where('created_by', $employee->user_id)->where('status', 'completed')->sum('disbursed_amount'),
            ],
            'recentLeads' => Lead::query()->where('assigned_to', $employee->user_id)->with(['customer', 'leadStatus'])->latest()->limit(8)->get(),
            'attendance' => Attendance::query()->where('employee_id', $employee->id)->latest('attendance_date')->limit(10)->get(),
            'permissions' => Permission::query()->orderBy('module')->get()->groupBy('module'),
        ]);
    }

    public function edit(Employee $employee): View
    {
        abort_unless(auth()->user()->hasPermissionTo('employees.manage'), 403);

        return view('employees.form', [
            'employee' => $employee,
            'user' => $employee->user,
            'departments' => Department::query()->active()->ordered()->get(),
            'designations' => Designation::query()->active()->ordered()->get(),
            'roles' => Role::query()->where('is_active', true)->orderBy('level')->get(),
        ]);
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $employee) {
            $employee->user->update(array_filter([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role_id' => $data['role_id'],
                'designation' => $data['designation'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'status' => $data['status'] ?? $employee->user->status,
            ], fn ($value) => $value !== null));

            if (! empty($data['password'])) {
                $employee->user->forceFill(['password' => Hash::make($data['password'])])->save();
            }

            $employee->user->extraPermissions()->sync($data['permissions'] ?? []);

            $employee->update(array_filter([
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'monthly_target' => $data['monthly_target'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_ifsc' => $data['bank_ifsc'] ?? null,
            ], fn ($value) => $value !== null));
        });

        return redirect()->route('employees.show', $employee)->with('success', 'Employee profile updated.');
    }

    public function toggleStatus(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('employees.manage'), 403);

        $employee->user->forceFill([
            'status' => $employee->user->isActive() ? 'inactive' : 'active',
        ])->save();

        $state = $employee->user->status;

        if ($request->expectsJson()) {
            return $this->ok('Employee account '.$state.'.', ['status' => $state]);
        }

        return back()->with('success', 'Employee account '.$state.'.');
    }

    public function export(Request $request, ExportService $export)
    {
        abort_unless($request->user()->hasPermissionTo('employees.view'), 403);

        $rows = Employee::query()
            ->with(['user.role', 'department', 'designation'])
            ->orderBy('employee_code')
            ->limit(5000)
            ->get()
            ->map(fn (Employee $e) => [
                $e->employee_code, $e->user?->name, $e->user?->email, $e->user?->role?->name,
                $e->designation?->name ?? $e->user?->designation, $e->department?->name,
                $e->mobile, $e->city, $e->joining_date?->format('d M Y'), $e->employment_status,
            ]);

        return $export->xlsx('employees-'.now()->format('Ymd-His').'.xlsx', [
            'Employee Code', 'Name', 'Email', 'Role', 'Designation', 'Department', 'Mobile', 'City', 'Joining Date', 'Status',
        ], $rows, 'Employees', [
            'Report' => 'Employee Directory',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }
}
