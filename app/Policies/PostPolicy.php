<?php

namespace App\Policies;

use App\Support\Permissions;

class PostPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
