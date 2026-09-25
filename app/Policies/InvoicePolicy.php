<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class InvoicePolicy
{
    use ChecksOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.view') && $this->owns($user, $invoice, 'created_by', 'issued_by');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoices.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.manage') && $this->owns($user, $invoice, 'created_by', 'issued_by');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.manage') && $this->owns($user, $invoice, 'created_by', 'issued_by');
    }
}
