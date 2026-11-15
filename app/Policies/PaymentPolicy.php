<?php

namespace App\Policies;

class PaymentPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'payments';
    }
}
