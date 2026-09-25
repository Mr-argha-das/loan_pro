<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class CustomerPolicy
{
    use ChecksOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.view') && $this->owns($user, $customer, 'created_by', 'assigned_employee_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.update') && $this->owns($user, $customer, 'created_by', 'assigned_employee_id');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.delete') && $this->owns($user, $customer, 'created_by', 'assigned_employee_id');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('customers.export');
    }
}
