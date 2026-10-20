<?php

namespace App\Policies;

use App\Support\Permissions;

class NotePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::CRM_MANAGE;
    }

    protected function viewPermission(): string
    {
        return Permissions::CRM_VIEW;
    }
}
