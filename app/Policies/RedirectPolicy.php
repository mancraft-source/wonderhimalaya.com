<?php

namespace App\Policies;

class RedirectPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'redirects';
    }
}
