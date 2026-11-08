<?php

namespace App\Policies;

use App\Support\Permissions;

class NotFoundLogPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
