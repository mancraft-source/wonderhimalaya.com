<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for records governed by one special permission (e.g. users.manage, settings.manage)
 * rather than a view/create/update/delete section. Admins pass via Gate::before.
 */
abstract class SinglePermissionPolicy
{
    abstract protected function permission(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->permission());
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
        return $user->isAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->permission());
    }
}
