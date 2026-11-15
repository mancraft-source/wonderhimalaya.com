<?php

namespace App\Policies;

class DifficultyPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'difficulties';
    }
}
