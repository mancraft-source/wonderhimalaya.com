<?php

namespace App\Policies;

use App\Support\Permissions;

class RedirectPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
