<?php

use App\Filament\Exports\LeadExporter;
use App\Filament\Exports\PaymentExporter;
use App\Filament\Pages\ManageHomepage;
use App\Filament\Pages\ManageNotifications;
use App\Filament\Pages\ManageSeoSettings;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Pages\MediaLibrary;
use App\Filament\Pages\SeoOverview;
use App\Filament\Pages\SystemStatus;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\ExportActions;
use App\Filament\Widgets\RevenueOverview;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TodayOverview;
use App\Filament\Widgets\UpcomingDepartures;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CrmTask;
use App\Models\Destination;
use App\Models\Difficulty;
use App\Models\Lead;
use App\Models\Menu;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Quote;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\Tier;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** The coarse roles exactly as they were before granular permissions. */
const LEGACY_ROLES = [
    'admin' => ['catalogue.manage', 'content.manage', 'crm.view', 'crm.manage', 'crm.view_all', 'payments.manage', 'users.manage', 'settings.manage', 'api.access'],
    'manager' => ['catalogue.manage', 'content.manage', 'crm.view', 'crm.manage', 'crm.view_all', 'payments.manage', 'settings.manage', 'api.access'],
    'sales' => ['crm.view', 'crm.manage', 'payments.manage'],
    'content' => ['catalogue.manage', 'content.manage'],
];

/**
 * Each model and the coarse permissions that gated it before: [view permissions (any of), manage permission].
 *
 * @return array<class-string, array{0: array<int, string>, 1: string}>
 */
function legacyModelGates(): array
{
    $crm = [['crm.view', 'crm.manage'], 'crm.manage'];
    $catalogue = [['catalogue.manage'], 'catalogue.manage'];
    $content = [['content.manage'], 'content.manage'];

    return [
        Lead::class => $crm, Contact::class => $crm, Company::class => $crm, CrmTask::class => $crm, Quote::class => $crm, Booking::class => $crm,
        Payment::class => [['payments.manage'], 'payments.manage'],
        Trip::class => $catalogue, TripDeparture::class => $catalogue, Destination::class => $catalogue, Activity::class => $catalogue,
        Difficulty::class => $catalogue, Tier::class => $catalogue, Tag::class => $catalogue,
        Page::class => $content, Post::class => $content, PostCategory::class => $content, TeamMember::class => $content,
        Testimonial::class => $content, Menu::class => $content, Redirect::class => $content, NotFoundLog::class => $content,
    ];
}

/**
 * Pages, widgets and helpers with the coarse permission(s) that gated them (any of).
 *
 * @return array<string, array{0: Closure(): bool, 1: array<int, string>}>
 */
function legacyScreenGates(): array
{
    return [
        'media library' => [fn () => MediaLibrary::canAccess(), ['content.manage']],
        'homepage content' => [fn () => ManageHomepage::canAccess(), ['content.manage']],
        'seo overview' => [fn () => SeoOverview::canAccess(), ['content.manage']],
        'seo settings' => [fn () => ManageSeoSettings::canAccess(), ['settings.manage']],
        'site settings' => [fn () => ManageSiteSettings::canAccess(), ['settings.manage']],
        'notification settings' => [fn () => ManageNotifications::canAccess(), ['settings.manage']],
        'system page' => [fn () => SystemStatus::canAccess(), ['settings.manage']],
        'activity log' => [fn () => ActivityLogResource::canViewAny(), ['settings.manage']],
        'users' => [fn () => UserResource::canViewAny(), ['users.manage']],
        'roles' => [fn () => RoleResource::canViewAny(), ['users.manage']],
        'lead export' => [fn () => ExportActions::canExport(LeadExporter::class), ['crm.view', 'crm.manage']],
        'payment export' => [fn () => ExportActions::canExport(PaymentExporter::class), ['payments.manage']],
        'stats widget' => [fn () => StatsOverview::canView(), ['crm.view']],
        'revenue widget' => [fn () => RevenueOverview::canView(), ['payments.manage']],
        'today widget' => [fn () => TodayOverview::canView(), ['crm.view', 'catalogue.manage']],
        'departures widget' => [fn () => UpcomingDepartures::canView(), ['crm.view', 'catalogue.manage']],
        'api docs' => [fn () => Gate::allows('viewApiDocs'), ['api.access']],
    ];
}

/** Replace the seeded catalogue with the pre-migration state: coarse permissions and roles, one user per role. */
function seedLegacyRoles(): array
{
    DB::table('role_has_permissions')->delete();
    DB::table('model_has_roles')->delete();
    DB::table('permissions')->delete();
    DB::table('roles')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $users = [];

    foreach (LEGACY_ROLES as $role => $permissions) {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        $users[$role] = User::factory()->role($role)->create();
    }

    return $users;
}

function runPermissionMigration(): void
{
    (require database_path('migrations/2026_11_15_095028_migrate_to_granular_permissions.php'))->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

it('gives every existing role exactly the access it had before', function (string $role) {
    $users = seedLegacyRoles();
    runPermissionMigration();
    runPermissionMigration();

    $user = $users[$role]->fresh();
    $legacy = LEGACY_ROLES[$role];
    $had = fn (array $anyOf): bool => array_intersect($anyOf, $legacy) !== [];
    $this->actingAs($user);

    foreach (legacyModelGates() as $model => [$view, $manage]) {
        $record = new $model;
        $canManage = $had([$manage]);
        $canDelete = $model === Lead::class ? $canManage && $had(['crm.view_all']) : $canManage;

        expect([
            'viewAny' => $user->can('viewAny', $model),
            'view' => $user->can('view', $record),
            'create' => $user->can('create', $model),
            'update' => $user->can('update', $record),
            'delete' => $user->can('delete', $record),
            'restore' => $user->can('restore', $record),
        ])->toBe([
            'viewAny' => $had($view),
            'view' => $had($view),
            'create' => $canManage,
            'update' => $canManage,
            'delete' => $canDelete,
            'restore' => $canManage,
        ], "{$role} on {$model}");
    }

    foreach (legacyScreenGates() as $screen => [$check, $anyOf]) {
        expect($check())->toBe($had($anyOf), "{$role} on {$screen}");
    }

    expect($user->can(Permissions::LEADS_VIEW_ALL))->toBe($had(['crm.view_all']));
})->with(array_keys(LEGACY_ROLES));

it('maps directly assigned permissions and removes the coarse ones', function () {
    seedLegacyRoles();
    $user = User::factory()->create();
    $user->givePermissionTo('crm.view');

    runPermissionMigration();

    expect($user->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->all())
        ->toBe(collect(['leads.view', 'contacts.view', 'companies.view', 'tasks.view', 'quotes.view', 'bookings.view', 'exports.run'])->sort()->values()->all())
        ->and(Permission::query()->whereIn('name', ['crm.view', 'crm.manage', 'crm.view_all', 'catalogue.manage', 'content.manage', 'payments.manage'])->exists())->toBeFalse()
        ->and(Permission::query()->pluck('name')->sort()->values()->all())->toBe(collect(Permissions::all())->sort()->values()->all());
});

it('seeds fresh installs with the same roles the migration produces', function () {
    seedLegacyRoles();
    runPermissionMigration();
    $migrated = Role::with('permissions')->get()->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')->sort()->values()->all()]);

    DB::table('role_has_permissions')->delete();
    DB::table('model_has_roles')->delete();
    DB::table('roles')->delete();
    $this->seed(RolesAndPermissionsSeeder::class);
    $seeded = Role::with('permissions')->get()->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')->sort()->values()->all()]);

    expect($seeded->sortKeys()->all())->toBe($migrated->sortKeys()->all());
});

it('keeps customised default roles when the seeder runs again', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Role::findByName('sales', 'web')->syncPermissions(['contacts.view']);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName('sales', 'web')->permissions->pluck('name')->all())->toBe(['contacts.view'])
        ->and(Role::findByName('admin', 'web')->permissions)->toHaveCount(count(Permissions::all()));
});
