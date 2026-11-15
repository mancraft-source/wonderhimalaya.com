<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Safety rails for roles and users: the locked admin role, the last active admin,
 * no privilege escalation, and activity-log entries for access changes.
 */
final class AccessControl
{
    public static function isLockedRole(Role $role): bool
    {
        return $role->name === Permissions::ADMIN_ROLE;
    }

    /** @return array<int, string> Permissions the actor may hand out (admins: everything). */
    public static function grantablePermissions(User $actor): array
    {
        if ($actor->isAdmin()) {
            return Permissions::all();
        }

        return array_values(array_intersect(Permissions::all(), $actor->getAllPermissions()->pluck('name')->all()));
    }

    /**
     * Permissions the actor is trying to add but does not hold themselves.
     *
     * @param  array<int, string>  $permissions  The new set.
     * @param  array<int, string>  $current  What the role already had (keeping those is never escalation).
     * @return array<int, string>
     */
    public static function escalations(User $actor, array $permissions, array $current = []): array
    {
        return array_values(array_diff($permissions, $current, self::grantablePermissions($actor)));
    }

    /**
     * Roles the actor may assign to someone: never admin unless the actor is an admin,
     * and only roles whose permissions the actor holds.
     *
     * @return Builder<Role>
     */
    public static function assignableRolesQuery(User $actor, Builder $query): Builder
    {
        if ($actor->isAdmin()) {
            return $query;
        }

        $grantable = self::grantablePermissions($actor);

        return $query
            ->where('name', '!=', Permissions::ADMIN_ROLE)
            ->whereDoesntHave('permissions', fn (Builder $permissions): Builder => $permissions->whereNotIn('name', $grantable));
    }

    /** @param  array<int, int|string>  $roleIds */
    public static function canAssignRoles(User $actor, array $roleIds): bool
    {
        if ($roleIds === []) {
            return true;
        }

        $assignable = self::assignableRolesQuery($actor, Role::query())->whereKey($roleIds)->count();

        return $assignable === count(array_unique($roleIds));
    }

    /** Whether this user is the only active admin left. */
    public static function isLastActiveAdmin(User $user): bool
    {
        if (! $user->is_active || ! $user->hasRole(Permissions::ADMIN_ROLE)) {
            return false;
        }

        return User::query()->role(Permissions::ADMIN_ROLE)->where('is_active', true)->whereKeyNot($user->getKey())->doesntExist();
    }

    /**
     * Whether saving this user with these roles / active flag would leave no active admin.
     *
     * @param  array<int, int|string>  $roleIds
     */
    public static function wouldRemoveLastAdmin(User $user, array $roleIds, bool $isActive): bool
    {
        if (! self::isLastActiveAdmin($user)) {
            return false;
        }

        $keepsAdmin = Role::query()->whereKey($roleIds)->where('name', Permissions::ADMIN_ROLE)->exists();

        return ! $keepsAdmin || ! $isActive;
    }

    /**
     * Sync a role's permissions and record the change (causer, old/new permission lists) in the activity log.
     *
     * @param  array<int, string>  $permissions
     * @param  array<string, mixed>  $oldAttributes  name/description before an update.
     */
    public static function syncRole(Role $role, array $permissions, string $event, ?User $causer, array $oldAttributes = []): void
    {
        $old = self::permissionNames($role);
        $new = self::isLockedRole($role) ? Permissions::all() : Permissions::normalize($permissions);

        $role->syncPermissions($new);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $attributes = ['name' => $role->name, 'description' => $role->description];
        $changedAttributes = array_filter($attributes, fn ($value, string $key): bool => ! array_key_exists($key, $oldAttributes) || $oldAttributes[$key] !== $value, ARRAY_FILTER_USE_BOTH);
        $added = array_values(array_diff($new, $old));
        $removed = array_values(array_diff($old, $new));

        if ($event === 'updated' && $added === [] && $removed === [] && $changedAttributes === []) {
            return;
        }

        $changes = $event === 'created'
            ? ['attributes' => [...$attributes, 'permissions' => $new]]
            : [
                'attributes' => [...array_intersect_key($attributes, $changedAttributes), 'permissions' => $new],
                'old' => [...array_intersect_key($oldAttributes, $changedAttributes), 'permissions' => $old],
            ];

        activity()
            ->performedOn($role)
            ->causedBy($causer)
            ->event($event)
            ->withChanges($changes)
            ->withProperties(['added' => $added, 'removed' => $removed])
            ->log($event);
    }

    public static function logRoleDeleted(Role $role, ?User $causer): void
    {
        activity()
            ->performedOn($role)
            ->causedBy($causer)
            ->event('deleted')
            ->withChanges(['old' => ['name' => $role->name, 'permissions' => self::permissionNames($role)]])
            ->log('deleted');
    }

    /**
     * Record a change to the roles a user holds.
     *
     * @param  array<int, string>  $oldRoles
     * @param  array<int, string>  $newRoles
     */
    public static function logUserRoles(User $user, array $oldRoles, array $newRoles, ?User $causer): void
    {
        sort($oldRoles);
        sort($newRoles);

        if ($oldRoles === $newRoles) {
            return;
        }

        activity()
            ->performedOn($user)
            ->causedBy($causer)
            ->event('updated')
            ->withChanges(['attributes' => ['roles' => $newRoles], 'old' => ['roles' => $oldRoles]])
            ->log('updated');
    }

    /** @return array<int, string> */
    public static function permissionNames(Role $role): array
    {
        return $role->permissions()->pluck('name')->sort()->values()->all();
    }

    /**
     * Union of the permissions of the given roles (admin: everything).
     *
     * @param  array<int, int|string>|Collection<int, int|string>  $roleIds
     * @return array<int, string>|null null when the roles include admin.
     */
    public static function permissionsForRoles(array|Collection $roleIds): ?array
    {
        $roles = Role::query()->whereKey(collect($roleIds)->all())->with('permissions')->get();

        if ($roles->contains('name', Permissions::ADMIN_ROLE)) {
            return null;
        }

        return $roles->flatMap(fn (Role $role) => $role->permissions->pluck('name'))->unique()->values()->all();
    }

    /** "Effective access" line for a set of roles. */
    public static function summaryForRoles(array|Collection $roleIds): string
    {
        $permissions = self::permissionsForRoles($roleIds);

        return $permissions === null ? 'Everything — administrators pass every check.' : Permissions::summarize($permissions);
    }
}
