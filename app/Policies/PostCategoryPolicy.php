<?php

namespace App\Policies;

use App\Support\Permissions;

class PostCategoryPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CONTENT;
    }
}
