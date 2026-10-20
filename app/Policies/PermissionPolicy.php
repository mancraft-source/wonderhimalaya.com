<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy: every ability maps to one permission per area
 * (see App\Support\Permissions). Admins pass via Gate::before.
 */
abstract class PermissionPolicy
{
    abstract protected function permission(): string;

    protected function viewPermission(): string
    {
        return $this->permission();
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->viewPermission()) || $user->can($this->permission());
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permission());
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permission());
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->can($this->permission());
    }

    public function restoreAny(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->permission());
    }
}
