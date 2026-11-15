<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AccessControl;
use App\Support\Permissions;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Models\Role;

/**
 * Settings → Roles & permissions (users.manage). The admin role is locked and a role
 * still assigned to people cannot be deleted — for admins too (see Gate::before).
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::USERS);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::USERS);
    }

    public function update(User $user, Role $role): Response
    {
        if (AccessControl::isLockedRole($role)) {
            return Response::deny('The admin role always has every permission.');
        }

        return $user->can(Permissions::USERS) ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Role $role): Response
    {
        if (AccessControl::isLockedRole($role)) {
            return Response::deny('The admin role cannot be deleted.');
        }

        $holders = $role->users()->count();

        if ($holders > 0) {
            return Response::deny('Still assigned to '.$holders.' '.str('person')->plural($holders).'. Give them another role first.');
        }

        return $user->can(Permissions::USERS) ? Response::allow() : Response::deny();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $role): Response
    {
        return $this->delete($user, $role);
    }
}
