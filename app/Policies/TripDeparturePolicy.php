<?php

namespace App\Policies;

use App\Support\Permissions;

class TripDeparturePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CATALOGUE;
    }
}
