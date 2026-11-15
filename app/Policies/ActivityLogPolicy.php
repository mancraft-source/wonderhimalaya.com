<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** The activity log (spatie) is a read-only audit trail for staff with activity_log.view. */
class ActivityLogPolicy extends SinglePermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::ACTIVITY_LOG;
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
