<?php

namespace App\Policies;

class CrmTaskPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'tasks';
    }
}
