<?php

namespace App\Policies;

use App\Support\Permissions;

class TripPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CATALOGUE;
    }
}
