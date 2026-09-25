<?php

namespace App\Policies;

use App\Models\Disbursement;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class DisbursementPolicy
{
    use ChecksOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('disbursements.view');
    }

    public function view(User $user, Disbursement $disbursement): bool
    {
        return $user->hasPermissionTo('disbursements.view') && $this->owns($user, $disbursement, 'created_by', 'approved_by');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('disbursements.manage');
    }

    public function update(User $user, Disbursement $disbursement): bool
    {
        return $user->hasPermissionTo('disbursements.manage') && $this->owns($user, $disbursement, 'created_by', 'approved_by');
    }

    public function changeStatus(User $user, Disbursement $disbursement): bool
    {
        return $user->hasPermissionTo('disbursements.manage') && $this->owns($user, $disbursement, 'created_by', 'approved_by');
    }
}
