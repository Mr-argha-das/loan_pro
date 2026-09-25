<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class PaymentPolicy
{
    use ChecksOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.view') && $this->owns($user, $payment, 'created_by', 'received_by');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('payments.manage');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.manage') && $this->owns($user, $payment, 'created_by', 'received_by');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.manage') && $this->owns($user, $payment, 'created_by', 'received_by');
    }
}
