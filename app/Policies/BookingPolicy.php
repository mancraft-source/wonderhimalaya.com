<?php

namespace App\Policies;

class BookingPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'bookings';
    }
}
