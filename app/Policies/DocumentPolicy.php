<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksOwnership;

class DocumentPolicy
{
    use ChecksOwnership;

    public function view(User $user, Document $document): bool
    {
        // Documents inherit the visibility of the record they belong to.
        $parent = $document->documentable;

        if (! $parent) {
            return $this->owns($user, $document, 'uploaded_by', null);
        }

        return $user->can('view', $parent) || $this->owns($user, $document, 'uploaded_by', null);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('documents.upload');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('documents.upload') && $this->owns($user, $document, 'uploaded_by', null);
    }

    public function verify(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('documents.verify');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('documents.delete') && $this->owns($user, $document, 'uploaded_by', null);
    }
}
