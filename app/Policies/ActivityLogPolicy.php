<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** The activity log (spatie) is a read-only audit trail for admins and managers. */
class ActivityLogPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::SETTINGS;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
