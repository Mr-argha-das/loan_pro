<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Shared ownership rule: an employee may only touch records they created or
 * that are assigned to them. Administrators are granted everything in
 * AppServiceProvider::boot() via Gate::before.
 */
trait ChecksOwnership
{
    protected function owns(User $user, object $record, string $createdColumn = 'created_by', ?string $assignedColumn = 'assigned_to'): bool
    {
        if ((int) ($record->{$createdColumn} ?? 0) === (int) $user->id) {
            return true;
        }

        if ($assignedColumn && (int) ($record->{$assignedColumn} ?? 0) === (int) $user->id) {
            return true;
        }

        return false;
    }
}
