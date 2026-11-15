<?php

namespace App\Policies;

class TripDeparturePolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'departures';
    }
}
