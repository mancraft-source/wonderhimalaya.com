<?php

namespace App\Policies;

class DestinationPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'destinations';
    }
}
