<?php

namespace App\Policies;

class ActivityPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'activities';
    }
}
