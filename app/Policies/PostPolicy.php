<?php

namespace App\Policies;

class PostPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'posts';
    }
}
