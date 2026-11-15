<?php

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Models\User;
use App\Support\Permissions;
use Database\Factories\LeadFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('renders the branded empty state with a create action on an empty table', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ListLeads::class)
        ->assertSee('No enquiries yet')
        ->assertSee('New enquiries from the website, the tailor-made planner and the API land here automatically.')
        ->assertActionVisible(TestAction::make('create')->table());
});

it('hides the empty state create action from users who cannot create', function () {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permissions::for('leads', Permissions::VIEW)));

    Livewire::test(ListLeads::class)
        ->assertSee('No enquiries yet')
        ->assertActionHidden(TestAction::make('create')->table());
});

it('says nothing matches when a tab or search hides existing records', function () {
    $this->actingAs(User::factory()->admin()->create());
    LeadFactory::new()->status(LeadStatus::New)->create();

    Livewire::test(ListLeads::class, ['activeTab' => 'lost'])
        ->assertSee('No matching leads')
        ->assertDontSee('No enquiries yet');
    Livewire::test(ListLeads::class)
        ->set('tableSearch', 'nobody-matches-this')
        ->assertSee('No matching leads');
});

it('celebrates an empty 404 monitor', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ListNotFoundLogs::class)
        ->assertSee('Good news — nobody has hit a broken link yet.');
});

it('remembers the table search between visits', function () {
    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ListLeads::class)->set('tableSearch', 'Everest');

    Livewire::test(ListLeads::class)->assertSet('tableSearch', 'Everest');
});
