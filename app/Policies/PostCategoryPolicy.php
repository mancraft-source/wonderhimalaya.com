<?php

namespace App\Policies;

class PostCategoryPolicy extends PermissionPolicy
{
    protected function section(): string
    {
        return 'post_categories';
    }
}
