<?php

namespace App\Policies;

use App\Support\Permissions;

class MenuPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
