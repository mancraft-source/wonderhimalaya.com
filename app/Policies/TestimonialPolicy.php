<?php

namespace App\Policies;

class TestimonialPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'testimonials';
    }
}
