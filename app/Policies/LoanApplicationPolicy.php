<?php

namespace App\Policies;

use App\Models\LoanApplication;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class LoanApplicationPolicy
{
    use ChecksOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications.view');
    }

    public function view(User $user, LoanApplication $application): bool
    {
        return $user->hasPermissionTo('applications.view') && $this->owns($user, $application);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('applications.create');
    }

    public function update(User $user, LoanApplication $application): bool
    {
        return $user->hasPermissionTo('applications.update') && $this->owns($user, $application);
    }

    public function delete(User $user, LoanApplication $application): bool
    {
        return $user->hasPermissionTo('applications.delete') && $this->owns($user, $application);
    }

    public function approve(User $user, LoanApplication $application): bool
    {
        return $user->hasPermissionTo('applications.approve') && $this->owns($user, $application);
    }
}
