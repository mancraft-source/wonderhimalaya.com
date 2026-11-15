<?php

namespace App\Policies;

class NotFoundLogPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'not_found_logs';
    }
}
