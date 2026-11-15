<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy: each policy names its admin section (see App\Support\Permissions::SECTIONS) and
 * abilities map to "{section}.view|create|update|delete". Restoring a deleted record (non-destructive)
 * needs update, reordering needs update, force-deleting is admin-only. Admins pass via Gate::before.
 */
abstract class PermissionPolicy
{
    abstract protected function section(): string;

    protected function allows(User $user, string $ability): bool
    {
        return $user->can(Permissions::for($this->section(), $ability));
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permissions::VIEW);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permissions::CREATE);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, Permissions::UPDATE);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, Permissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, Permissions::DELETE);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, Permissions::UPDATE);
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, Permissions::UPDATE);
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
        return $this->allows($user, Permissions::UPDATE);
    }
}
