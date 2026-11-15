<?php

namespace App\Filament\Resources\Roles;

use App\Models\User;
use App\Support\AccessControl;
use App\Support\Permissions;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Spatie\Permission\Models\Role;

/** Moves a role's permissions between the database and the Roles & permissions form. */
final class RolePermissions
{
    /**
     * @return array{section_permissions: array<int, string>, special_permissions: array<int, string>}
     */
    public static function fill(Role $role): array
    {
        $names = AccessControl::isLockedRole($role) ? Permissions::all() : AccessControl::permissionNames($role);

        return [
            'section_permissions' => array_values(array_intersect($names, Permissions::sectionPermissions())),
            'special_permissions' => array_values(array_intersect($names, Permissions::specialPermissions())),
        ];
    }

    /**
     * The permissions chosen in the form, normalised.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function fromForm(array $data): array
    {
        return Permissions::normalize([
            ...array_filter((array) ($data['section_permissions'] ?? []), 'is_string'),
            ...array_filter((array) ($data['special_permissions'] ?? []), 'is_string'),
        ]);
    }

    /**
     * Stops the save when the actor tries to grant permissions they do not hold themselves.
     *
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $current
     *
     * @throws Halt
     */
    public static function guardEscalation(array $permissions, array $current = []): void
    {
        $actor = auth()->user();
        $escalations = $actor instanceof User ? AccessControl::escalations($actor, $permissions, $current) : $permissions;

        if ($escalations === []) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('You cannot grant permissions you do not have')
            ->body(implode(', ', array_map(Permissions::label(...), $escalations)))
            ->send();

        throw new Halt;
    }
}
