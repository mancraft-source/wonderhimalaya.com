<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AccessControl;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends SinglePermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::USERS;
    }

    /** Nobody deletes their own account, and the last active admin cannot be deleted (admins included, see Gate::before). */
    public function delete(User $user, Model $model): bool
    {
        /** @var User $model */
        return ! $model->is($user)
            && ! AccessControl::isLastActiveAdmin($model)
            && parent::delete($user, $model);
    }
}
