<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Tables\RolesTable;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Permission\Models\Role;

/** Read-only view of a role (the locked admin role is only ever shown here). */
class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            RolesTable::duplicateAction(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        return [...$data, ...RolePermissions::fill($role)];
    }
}
