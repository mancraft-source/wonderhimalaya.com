<?php

namespace App\Policies;

class TierPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'tiers';
    }
}
