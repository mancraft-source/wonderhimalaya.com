<?php

namespace App\Policies;

use App\Support\Permissions;

class TagPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CATALOGUE;
    }
}
