<?php

namespace App\Policies;

use App\Support\Permissions;

class TestimonialPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
