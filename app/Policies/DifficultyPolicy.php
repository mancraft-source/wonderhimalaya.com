<?php

namespace App\Policies;

use App\Support\Permissions;

class DifficultyPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CATALOGUE;
    }
}
