<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employees.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees.view') || (int) $employee->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employees.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees.manage');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees.manage') && (int) $employee->user_id !== (int) $user->id;
    }
}
