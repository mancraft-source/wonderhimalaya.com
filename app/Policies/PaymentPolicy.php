<?php

namespace App\Policies;

use App\Support\Permissions;

class PaymentPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::PAYMENTS;
    }
}
