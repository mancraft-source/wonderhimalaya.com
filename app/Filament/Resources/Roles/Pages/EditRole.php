<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Support\AccessControl;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RolesTable::duplicateAction(),
            RolesTable::deleteAction(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        return [...$data, ...RolePermissions::fill($role)];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        abort_if(AccessControl::isLockedRole($record), 403);

        $permissions = RolePermissions::fromForm($data);
        RolePermissions::guardEscalation($permissions, AccessControl::permissionNames($record));

        $oldAttributes = ['name' => $record->name, 'description' => $record->description];

        $record->update([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        AccessControl::syncRole($record, $permissions, 'updated', auth()->user(), $oldAttributes);

        return $record;
    }
}
