<?php

namespace App\Policies;

class PagePolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'pages';
    }
}
