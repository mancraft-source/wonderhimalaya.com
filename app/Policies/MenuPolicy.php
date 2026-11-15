<?php

namespace App\Policies;

class MenuPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'menus';
    }
}
