<?php

namespace App\Policies;

class TeamMemberPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'team_members';
    }
}
