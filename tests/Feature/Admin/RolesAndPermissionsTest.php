<?php

use App\Filament\Exports\ContactExporter;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\LeadPipeline;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\CrmTasks\CrmTaskResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\ExportActions;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TodayOverview;
use App\Models\User;
use App\Support\AccessControl;
use Database\Factories\ContactFactory;
use Database\Factories\LeadFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/** @param  array<int, string>  $permissions */
function staffWith(array $permissions, string $role = 'custom'): User
{
    Role::findOrCreate($role, 'web')->syncPermissions($permissions);

    return User::factory()->role($role)->create();
}

it('lets a contacts viewer list and open contacts but not change them or see other CRM sections', function () {
    $contact = ContactFactory::new()->create(['first_name' => 'Ang', 'last_name' => 'Dorje']);
    $this->actingAs(staffWith(['contacts.view']));

    $this->get(ContactResource::getUrl('index'))->assertOk()->assertSee('Ang Dorje');
    $this->get(ContactResource::getUrl('view', ['record' => $contact]))->assertOk();
    $this->get(ContactResource::getUrl('edit', ['record' => $contact]))->assertForbidden();
    $this->get(ContactResource::getUrl('create'))->assertForbidden();
    Livewire::test(ListContacts::class)
        ->assertActionHidden(TestAction::make('edit')->table($contact))
        ->assertActionHidden(TestAction::make('create'));
    expect(ContactResource::canDelete($contact))->toBeFalse();

    $this->get('/admin')->assertOk()
        ->assertSee(ContactResource::getUrl('index'))
        ->assertDontSee(LeadResource::getUrl('index'))
        ->assertDontSee(CompanyResource::getUrl('index'))
        ->assertDontSee(CrmTaskResource::getUrl('index'))
        ->assertDontSee(BookingResource::getUrl('index'));
    $this->get(LeadPipeline::getUrl())->assertForbidden();
});

it('lets the contact be edited once the role is granted contacts.update', function () {
    $contact = ContactFactory::new()->create();
    $this->actingAs(staffWith(['contacts.view', 'contacts.update']));

    $this->get(ContactResource::getUrl('edit', ['record' => $contact]))->assertOk();
    $this->get(ContactResource::getUrl('create'))->assertForbidden();
});

it('only exports, searches and shows dashboard widgets for what the role allows', function () {
    $this->actingAs(staffWith(['contacts.view']));

    expect(ExportActions::canExport(ContactExporter::class))->toBeFalse()
        ->and(ContactResource::canGloballySearch())->toBeTrue()
        ->and(LeadResource::canGloballySearch())->toBeFalse()
        ->and(StatsOverview::canView())->toBeFalse()
        ->and(TodayOverview::canView())->toBeFalse();

    $this->actingAs(staffWith(['contacts.view', 'exports.run', 'leads.view'], 'viewer'));

    expect(ExportActions::canExport(ContactExporter::class))->toBeTrue()
        ->and(StatsOverview::canView())->toBeTrue()
        ->and(TodayOverview::canView())->toBeTrue();
    $this->get(Dashboard::getUrl())->assertOk();
});

it('applies the role to API tokens on top of token abilities', function () {
    ContactFactory::new()->create();
    Sanctum::actingAs(staffWith(['contacts.view']), ['read']);

    $this->getJson('/api/v1/contacts')->assertOk();
    $this->getJson('/api/v1/leads')->assertForbidden();
});

it('limits Roles & permissions to users.manage', function (string $role, int $status) {
    $this->actingAs(User::factory()->role($role)->create())
        ->get(RoleResource::getUrl('index'))
        ->assertStatus($status);
})->with([
    'admin' => ['admin', 200],
    'manager' => ['manager', 403],
    'sales' => ['sales', 403],
]);

it('lists roles with how many people hold them', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->count(2)->sales()->create();

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords(Role::all())
        ->assertTableColumnStateSet('users_count', 2, Role::findByName('sales', 'web'));
});

it('creates a role from the matrix, adding view wherever another ability is ticked, and logs it', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'finance',
            'description' => 'Payments only',
            'section_permissions' => ['payments.update', 'bookings.view'],
            'special_permissions' => ['exports.run'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::findByName('finance', 'web');
    expect($role->description)->toBe('Payments only')
        ->and($role->permissions->pluck('name')->sort()->values()->all())->toBe(['bookings.view', 'exports.run', 'payments.update', 'payments.view']);

    $entry = Activity::query()->where('subject_type', 'role')->where('subject_id', $role->id)->sole();
    expect($entry->event)->toBe('created')
        ->and($entry->causer_id)->toBe($admin->id)
        ->and($entry->attribute_changes['attributes']['permissions'])->toContain('payments.view');
});

it('reserves the admin name', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Admin'])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('updates a role, logs old and new permissions and clears the permission cache', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $role = Role::findOrCreate('desk', 'web');
    $role->syncPermissions(['contacts.view']);
    $member = User::factory()->role('desk')->create();
    expect($member->can('contacts.update'))->toBeFalse();

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertFormSet(['section_permissions' => ['contacts.view']])
        ->fillForm(['section_permissions' => ['contacts.view', 'contacts.update']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(User::find($member->id)->can('contacts.update'))->toBeTrue();

    $entry = Activity::query()->where('subject_type', 'role')->where('event', 'updated')->sole();
    expect($entry->causer_id)->toBe($admin->id)
        ->and($entry->attribute_changes['old']['permissions'])->toBe(['contacts.view'])
        ->and($entry->attribute_changes['attributes']['permissions'])->toBe(['contacts.view', 'contacts.update'])
        ->and($entry->properties['added'])->toBe(['contacts.update']);
});

it('keeps the admin role locked and read-only', function () {
    $this->actingAs(User::factory()->admin()->create());
    $admin = Role::findByName('admin', 'web');

    $this->get(RoleResource::getUrl('edit', ['record' => $admin]))->assertForbidden();
    $this->get(RoleResource::getUrl('view', ['record' => $admin]))->assertOk()->assertSee('The admin role is locked');
    expect(RoleResource::canDelete($admin))->toBeFalse();
});

it('refuses to delete a role that people still hold, and deletes an unused one', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->sales()->create();
    $sales = Role::findByName('sales', 'web');
    $unused = Role::findOrCreate('unused', 'web');

    Livewire::test(ListRoles::class)
        ->assertActionDisabled(TestAction::make('delete')->table($sales))
        ->callAction(TestAction::make('delete')->table($unused));

    expect(Role::query()->where('name', 'sales')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'unused')->exists())->toBeFalse()
        ->and(Activity::query()->where('subject_type', 'role')->where('event', 'deleted')->exists())->toBeTrue();
});

it('duplicates a role with its permissions', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ListRoles::class)
        ->callAction(TestAction::make('duplicate')->table(Role::findByName('content', 'web')), data: ['name' => 'web team'])
        ->assertHasNoActionErrors();

    expect(Role::findByName('web team', 'web')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(Role::findByName('content', 'web')->permissions->pluck('name')->sort()->values()->all());
});

it('stops a user manager granting permissions they do not have', function () {
    $this->actingAs(staffWith(['users.manage', 'contacts.view', 'contacts.update'], 'people'));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'sneaky', 'section_permissions' => ['contacts.view', 'leads.view']])
        ->call('create')
        ->assertNotified('You cannot grant permissions you do not have');
    expect(Role::query()->where('name', 'sneaky')->exists())->toBeFalse();

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'helper', 'section_permissions' => ['contacts.view']])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Role::findByName('helper', 'web')->permissions->pluck('name')->all())->toBe(['contacts.view']);
});

it('stops a user manager assigning the admin role or a role beyond their own access', function () {
    $this->actingAs(staffWith(['users.manage', 'contacts.view'], 'people'));
    $colleague = User::factory()->create();
    $helper = Role::findOrCreate('helper', 'web');
    $helper->syncPermissions(['contacts.view']);

    foreach (['admin', 'manager'] as $role) {
        Livewire::test(EditUser::class, ['record' => $colleague->getRouteKey()])
            ->fillForm(['roles' => [Role::findByName($role, 'web')->id]])
            ->call('save');
        expect($colleague->fresh()->roles)->toBeEmpty();
    }

    $user = User::find(auth()->id());
    expect(AccessControl::canAssignRoles($user, [Role::findByName('manager', 'web')->id]))->toBeFalse()
        ->and(AccessControl::canAssignRoles($user, [Role::findByName('admin', 'web')->id]))->toBeFalse()
        ->and(AccessControl::canAssignRoles($user, [$helper->id]))->toBeTrue();

    Livewire::test(EditUser::class, ['record' => $colleague->getRouteKey()])
        ->fillForm(['roles' => [$helper->id]])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($colleague->fresh()->roles->pluck('name')->all())->toBe(['helper'])
        ->and(Activity::query()->where('subject_type', 'user')->where('subject_id', $colleague->id)->exists())->toBeTrue();
});

it('always keeps one active admin', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $adminRole = Role::findByName('admin', 'web');

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertNotified('At least one active admin is required');
    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['roles' => [Role::findByName('manager', 'web')->id]])
        ->call('save')
        ->assertNotified('At least one active admin is required');

    expect($admin->fresh()->is_active)->toBeTrue()
        ->and($admin->fresh()->hasRole('admin'))->toBeTrue();

    $other = User::factory()->admin()->inactive()->create();
    expect($other->can('delete', $admin))->toBeFalse();

    $other->update(['is_active' => true]);
    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['roles' => [$adminRole->id], 'is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($admin->fresh()->is_active)->toBeFalse();
});

it('summarises a user\'s effective access on their account', function () {
    $this->actingAs(User::factory()->admin()->create());
    $desk = Role::findOrCreate('desk', 'web');
    $desk->syncPermissions(['leads.view', 'leads.update', 'contacts.view', 'contacts.update', 'trips.view', 'exports.run']);
    $member = User::factory()->role('desk')->create();

    $this->get(UserResource::getUrl('edit', ['record' => $member]))
        ->assertOk()
        ->assertSee('View &amp; edit: Leads, Contacts · View: Trips · Special: Export', false);
});

it('scopes leads for staff without leads.view_all', function () {
    $user = staffWith(['leads.view']);
    $mine = LeadFactory::new()->ownedBy($user)->create();
    $theirs = LeadFactory::new()->ownedBy(User::factory()->create())->create();
    $this->actingAs($user);

    expect(LeadResource::getEloquentQuery()->pluck('id')->all())->toBe([$mine->id])
        ->and($user->can('view', $theirs))->toBeFalse();
});
