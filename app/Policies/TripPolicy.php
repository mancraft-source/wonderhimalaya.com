<?php

namespace App\Policies;

class TripPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'trips';
    }
}
