<?php

namespace App\Policies;

use App\Support\Permissions;

class UserPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::USERS;
    }
}
