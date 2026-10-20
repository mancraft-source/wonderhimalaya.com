<?php

namespace App\Policies;

use App\Support\Permissions;

class PagePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
