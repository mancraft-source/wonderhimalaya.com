<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Support\AccessControl;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $permissions = RolePermissions::fromForm($data);
        RolePermissions::guardEscalation($permissions);

        $role = Role::create([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'guard_name' => 'web',
        ]);

        AccessControl::syncRole($role, $permissions, 'created', auth()->user());

        return $role;
    }
}
