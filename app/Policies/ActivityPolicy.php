<?php

namespace App\Policies;

use App\Support\Permissions;

class ActivityPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CATALOGUE;
    }
}
