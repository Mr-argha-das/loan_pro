@extends('layouts.app')

@php
    $isEdit = $employee->exists;
    $action = $isEdit ? route('employees.update', $employee) : route('employees.store');
@endphp

@section('title', $isEdit ? 'Edit '.$user->name : 'Add Employee')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit employee' : 'Add new employee')
@section('page-subtitle', $isEdit ? $employee->employee_code.' · '.($employee->designation?->name ?? '') : 'Creates a login, employee profile and module permissions together.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $action }}" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Login details" icon="bi-person-badge" description="Credentials the employee uses to sign in.">
                    <div class="row g-3">
                        <div class="col-md-6"><x-input name="name" label="Full name" :value="old('name', $user->name)" required /></div>
                        <div class="col-md-6"><x-input name="email" label="Work email" type="email" :value="old('email', $user->email)" required /></div>
                        <div class="col-md-4"><x-input name="phone" label="Phone" :value="old('phone', $user->phone)" maxlength="10" /></div>
                        <div class="col-md-4">
                            <label class="form-label" for="role_id">Role<span class="req">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id) === $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <x-select name="status" label="Login status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']"
                                      :value="old('status', $user->status ?? 'active')" />
                        </div>
                        <div class="col-md-6">
                            <x-input name="password" label="{{ $isEdit ? 'New password (optional)' : 'Password' }}" type="password"
                                     :help="$isEdit ? 'Leave blank to keep the current password.' : 'Defaults to “password” when left blank.'" />
                        </div>
                        <div class="col-md-6"><x-input name="password_confirmation" label="Confirm password" type="password" /></div>
                    </div>
                </x-section>

                <x-section title="Employment" icon="bi-briefcase">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="department_id">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('department_id', $employee->department_id) === $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="designation_id">Designation</label>
                            <select class="form-select" id="designation_id" name="designation_id">
                                <option value="">Select designation</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}" @selected((int) old('designation_id', $employee->designation_id) === $designation->id)>{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <x-select name="employment_status" label="Employment status"
                                      :options="['active' => 'Active', 'probation' => 'Probation', 'notice' => 'Notice period', 'resigned' => 'Resigned', 'terminated' => 'Terminated']"
                                      :value="old('employment_status', $employee->employment_status ?? 'active')" />
                        </div>
                        <div class="col-md-4"><x-input name="joining_date" label="Joining date" type="date" :value="old('joining_date', $employee->joining_date?->toDateString() ?? now()->toDateString())" /></div>
                        <div class="col-md-4"><x-input name="monthly_target" label="Monthly target" type="number" step="1000" :value="old('monthly_target', $employee->monthly_target)" icon="bi-currency-rupee" /></div>
                        <div class="col-md-4"><x-input name="reporting_to" label="Reporting to (user id)" type="number" :value="old('reporting_to', $employee->reporting_to)" /></div>
                    </div>
                </x-section>

                <x-section title="Contact & bank" icon="bi-bank">
                    <div class="row g-3">
                        <div class="col-md-4"><x-input name="mobile" label="Mobile" :value="old('mobile', $employee->mobile)" maxlength="10" /></div>
                        <div class="col-md-4"><x-input name="alternate_mobile" label="Alternate mobile" :value="old('alternate_mobile', $employee->alternate_mobile)" maxlength="10" /></div>
                        <div class="col-md-4"><x-input name="city" label="City" :value="old('city', $employee->city)" /></div>
                        <div class="col-md-4"><x-input name="bank_name" label="Bank name" :value="old('bank_name', $employee->bank_name)" /></div>
                        <div class="col-md-4"><x-input name="bank_account_number" label="Account number" :value="old('bank_account_number', $employee->bank_account_number)" /></div>
                        <div class="col-md-4"><x-input name="bank_ifsc" label="IFSC" :value="old('bank_ifsc', $employee->bank_ifsc)" /></div>
                        <div class="col-md-6"><x-input name="emergency_contact_name" label="Emergency contact" :value="old('emergency_contact_name', $employee->emergency_contact_name)" /></div>
                        <div class="col-md-6"><x-input name="emergency_contact_number" label="Emergency number" :value="old('emergency_contact_number', $employee->emergency_contact_number)" maxlength="10" /></div>
                        <div class="col-12"><x-textarea name="address" label="Address" rows="2" :value="old('address', $employee->address)" /></div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                <x-section title="Extra permissions" icon="bi-shield-lock" description="Module permissions granted on top of the selected role.">
                    @php
                        $granted = old('permissions', $isEdit ? $user->extraPermissions->pluck('id')->all() : []);
                        $permissions = App\Models\Permission::query()->orderBy('module')->orderBy('name')->get()->groupBy('module');
                    @endphp

                    <div class="lp-permission-groups">
                        @foreach ($permissions as $module => $group)
                            <div class="mb-3">
                                <div class="lp-permission-group__title">{{ \App\Support\Format::titleCase($module) }}</div>
                                @foreach ($group as $permission)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                               id="permission-{{ $permission->id }}" @checked(in_array($permission->id, $granted, true))>
                                        <label class="form-check-label small" for="permission-{{ $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </x-section>

                <div class="d-flex gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> {{ $isEdit ? 'Update' : 'Create employee' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection
