<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->can('view', $document->folder);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Folder $folder): bool
    {
        return $user->can('editContents', $folder);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->can('editContents', $document->folder);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->can('editContents', $document->folder);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function uploadVersion(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
