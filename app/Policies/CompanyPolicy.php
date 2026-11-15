<?php

namespace App\Policies;

class CompanyPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'companies';
    }
}
