<?php

namespace App\Policies;

class ContactPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'contacts';
    }
}
