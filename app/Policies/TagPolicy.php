<?php

namespace App\Policies;

class TagPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'tags';
    }
}
