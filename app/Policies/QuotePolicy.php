<?php

namespace App\Policies;

class QuotePolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'quotes';
    }
}
