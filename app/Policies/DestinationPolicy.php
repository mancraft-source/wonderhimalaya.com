<?php

namespace App\Policies;

use App\Support\Permissions;

class DestinationPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CATALOGUE;
    }
}
