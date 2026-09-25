<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class LeadPolicy
{
    use ChecksOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->hasPermissionTo('leads.view') && $this->owns($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasPermissionTo('leads.update') && $this->owns($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasPermissionTo('leads.delete') && $this->owns($user, $lead);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->hasPermissionTo('leads.assign') && $this->owns($user, $lead);
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('leads.export');
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $user->hasPermissionTo('applications.create') && $this->owns($user, $lead);
    }
}
