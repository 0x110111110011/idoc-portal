<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FolderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Folder $folder): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($folder->created_by === $user->id) {
            return true;
        }

        return $folder->users()
            ->whereKey($user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Folder $folder): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $folder->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Folder $folder): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $folder->created_by === $user->id;
    }

    public function manageAccess(User $user, Folder $folder): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $folder->created_by === $user->id;
    }

    public function editContents(User $user, Folder $folder): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($folder->created_by === $user->id) {
            return true;
        }

        return $folder->users()
            ->whereKey($user->id)
            ->wherePivot('access', 'edit')
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Folder $folder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Folder $folder): bool
    {
        return false;
    }
}
