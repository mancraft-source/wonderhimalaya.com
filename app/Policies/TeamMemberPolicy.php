<?php

namespace App\Policies;

use App\Support\Permissions;

class TeamMemberPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
