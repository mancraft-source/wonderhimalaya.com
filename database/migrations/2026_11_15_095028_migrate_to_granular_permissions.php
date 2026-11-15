<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Replaces the coarse area permissions (crm.manage, catalogue.manage…) with granular
 * "{section}.{ability}" permissions plus special ones, granting every existing role and every
 * directly-assigned user exactly the access they had before. Idempotent: it only adds what is
 * missing, and the coarse permissions are removed once mapped, so a re-run maps nothing.
 *
 * The catalogue is frozen here on purpose (not read from App\Support\Permissions) so this
 * migration keeps doing the same thing if the catalogue changes later.
 */
return new class extends Migration
{
    private const GUARD = 'web';

    private const ABILITIES = ['view', 'create', 'update', 'delete'];

    private const CRM = ['leads', 'contacts', 'companies', 'tasks', 'quotes', 'bookings'];

    private const CATALOGUE = ['trips', 'departures', 'destinations', 'activities', 'difficulties', 'tiers', 'tags'];

    private const CONTENT = ['pages', 'posts', 'post_categories', 'team_members', 'testimonials', 'menus', 'redirects', 'not_found_logs'];

    private const MEDIA = ['media.view', 'media.update', 'media.delete'];

    private const SPECIAL = ['leads.view_all', 'exports.run', 'seo.manage', 'settings.manage', 'system.view', 'activity_log.view', 'users.manage', 'api.access'];

    /** Coarse permissions that go away (users.manage, settings.manage and api.access keep their names). */
    private const REMOVED = ['catalogue.manage', 'content.manage', 'crm.view', 'crm.manage', 'crm.view_all', 'payments.manage'];

    private const LEGACY = [...self::REMOVED, 'users.manage', 'settings.manage', 'api.access'];

    public function up(): void
    {
        $tables = config('permission.table_names');
        $pivotPermission = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $modelKey = config('permission.column_names.model_morph_key') ?: 'model_id';

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () use ($tables, $pivotPermission, $pivotRole, $modelKey): void {
            $ids = $this->ensurePermissions($tables['permissions']);
            $legacyIds = DB::table($tables['permissions'])->where('guard_name', self::GUARD)->whereIn('name', self::LEGACY)->pluck('name', 'id')->all();

            foreach (DB::table($tables['roles'])->where('guard_name', self::GUARD)->get(['id', 'name']) as $role) {
                $legacy = DB::table($tables['role_has_permissions'])
                    ->where($pivotRole, $role->id)
                    ->whereIn($pivotPermission, array_keys($legacyIds))
                    ->pluck($pivotPermission)
                    ->map(fn ($id): string => $legacyIds[$id])
                    ->all();

                $granted = $role->name === 'admin' ? $this->allPermissions() : $this->map($legacy);

                $existing = DB::table($tables['role_has_permissions'])->where($pivotRole, $role->id)->pluck($pivotPermission)->all();

                DB::table($tables['role_has_permissions'])->insert(array_values(array_map(
                    fn (string $name): array => [$pivotPermission => $ids[$name], $pivotRole => $role->id],
                    array_filter($granted, fn (string $name): bool => ! in_array($ids[$name], $existing)),
                )));
            }

            $direct = DB::table($tables['model_has_permissions'])
                ->whereIn($pivotPermission, array_keys($legacyIds))
                ->get([$pivotPermission, 'model_type', $modelKey])
                ->groupBy(fn ($row): string => $row->model_type.'|'.$row->{$modelKey});

            foreach ($direct as $rows) {
                $model = $rows->first();
                $existing = DB::table($tables['model_has_permissions'])
                    ->where('model_type', $model->model_type)
                    ->where($modelKey, $model->{$modelKey})
                    ->pluck($pivotPermission)
                    ->all();

                $granted = $this->map($rows->map(fn ($row): string => $legacyIds[$row->{$pivotPermission}])->all());

                DB::table($tables['model_has_permissions'])->insert(array_values(array_map(
                    fn (string $name): array => [$pivotPermission => $ids[$name], 'model_type' => $model->model_type, $modelKey => $model->{$modelKey}],
                    array_filter($granted, fn (string $name): bool => ! in_array($ids[$name], $existing)),
                )));
            }

            $removedIds = DB::table($tables['permissions'])->where('guard_name', self::GUARD)->whereIn('name', self::REMOVED)->pluck('id');
            DB::table($tables['role_has_permissions'])->whereIn($pivotPermission, $removedIds)->delete();
            DB::table($tables['model_has_permissions'])->whereIn($pivotPermission, $removedIds)->delete();
            DB::table($tables['permissions'])->whereIn('id', $removedIds)->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // One-way: the granular permissions are a superset of what the coarse ones could express.
    }

    /**
     * The granular equivalent of a set of coarse permissions, matching how each policy,
     * page and widget checked them before.
     *
     * @param  array<int, string>  $legacy
     * @return array<int, string>
     */
    private function map(array $legacy): array
    {
        $has = fn (string $permission): bool => in_array($permission, $legacy, true);
        $granted = [];

        if ($has('crm.view')) {
            $granted = [...$granted, ...$this->sections(self::CRM, ['view']), 'exports.run'];
        }

        if ($has('crm.manage')) {
            // Deleting a lead needed crm.view_all as well as crm.manage.
            $crm = array_diff($this->sections(self::CRM, self::ABILITIES), $has('crm.view_all') ? [] : ['leads.delete']);
            $granted = [...$granted, ...$crm, 'exports.run'];
        }

        if ($has('crm.view_all')) {
            $granted[] = 'leads.view_all';
        }

        if ($has('payments.manage')) {
            $granted = [...$granted, ...$this->sections(['payments'], self::ABILITIES), 'exports.run'];
        }

        if ($has('catalogue.manage')) {
            $granted = [...$granted, ...$this->sections(self::CATALOGUE, self::ABILITIES)];
        }

        if ($has('content.manage')) {
            $granted = [...$granted, ...$this->sections(self::CONTENT, self::ABILITIES), ...self::MEDIA];
        }

        if ($has('settings.manage')) {
            $granted = [...$granted, 'settings.manage', 'seo.manage', 'system.view', 'activity_log.view'];
        }

        foreach (['users.manage', 'api.access'] as $kept) {
            if ($has($kept)) {
                $granted[] = $kept;
            }
        }

        return array_values(array_unique($granted));
    }

    /** @return array<string, int> name => id for every granular permission (created when missing). */
    private function ensurePermissions(string $table): array
    {
        $existing = DB::table($table)->where('guard_name', self::GUARD)->pluck('name')->all();
        $now = now();

        DB::table($table)->insert(array_values(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => self::GUARD, 'created_at' => $now, 'updated_at' => $now],
            array_diff($this->allPermissions(), $existing),
        )));

        return DB::table($table)->where('guard_name', self::GUARD)->whereIn('name', $this->allPermissions())->pluck('id', 'name')->all();
    }

    /** @return array<int, string> */
    private function allPermissions(): array
    {
        return [
            ...$this->sections([...self::CRM, 'payments', ...self::CATALOGUE, ...self::CONTENT], self::ABILITIES),
            ...self::MEDIA,
            ...self::SPECIAL,
        ];
    }

    /**
     * @param  array<int, string>  $sections
     * @param  array<int, string>  $abilities
     * @return array<int, string>
     */
    private function sections(array $sections, array $abilities): array
    {
        $permissions = [];

        foreach ($sections as $section) {
            foreach ($abilities as $ability) {
                $permissions[] = $section.'.'.$ability;
            }
        }

        return $permissions;
    }
};
