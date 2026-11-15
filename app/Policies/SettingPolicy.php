<?php

namespace App\Policies;

use App\Support\Permissions;

class SettingPolicy extends SinglePermissionPolicy
{
    protected function permission(): string
    {
        return Permissions::SETTINGS;
    }
}
