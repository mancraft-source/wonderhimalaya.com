<?php

use App\Enums\LeadStatus;
use App\Filament\Pages\LeadPipeline;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Trips\TripResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Factories\LeadFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies the panel to users without a role', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('denies the panel to inactive staff', function () {
    $this->actingAs(User::factory()->inactive()->sales()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('lets sales staff into the CRM but not the catalogue or user admin', function () {
    $sales = User::factory()->sales()->create();
    $this->actingAs($sales);

    $this->get('/admin')->assertOk();
    $this->get(LeadResource::getUrl('index'))->assertOk();
    $this->get(LeadPipeline::getUrl())->assertOk();
    $this->get(PaymentResource::getUrl('index'))->assertOk();
    $this->get(TripResource::getUrl('index'))->assertForbidden();
    $this->get(TripResource::getUrl('create'))->assertForbidden();
    $this->get(UserResource::getUrl('index'))->assertForbidden();
});

it('lets content editors manage the catalogue but not the CRM', function () {
    $this->actingAs(User::factory()->role('content')->create());

    $this->get(TripResource::getUrl('index'))->assertOk();
    $this->get(LeadResource::getUrl('index'))->assertForbidden();
    $this->get(PaymentResource::getUrl('index'))->assertForbidden();
});

it('only shows sales staff their own and unassigned leads', function () {
    $sales = User::factory()->sales()->create();
    $colleague = User::factory()->sales()->create();

    $mine = LeadFactory::new()->ownedBy($sales)->create();
    $unassigned = LeadFactory::new()->ownedBy(null)->create();
    $theirs = LeadFactory::new()->ownedBy($colleague)->create();

    $this->actingAs($sales);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$mine, $unassigned])
        ->assertCanNotSeeTableRecords([$theirs]);

    $this->get(LeadResource::getUrl('view', ['record' => $mine]))->assertOk();
    $this->get(LeadResource::getUrl('view', ['record' => $theirs]))->assertNotFound();
    $this->get(LeadResource::getUrl('edit', ['record' => $theirs]))->assertNotFound();

    expect(LeadResource::getNavigationBadge())->toBe('2');
});

it('shows managers every lead', function () {
    $manager = User::factory()->manager()->create();
    $leads = LeadFactory::new()->count(2)->ownedBy(User::factory()->sales()->create())->create();

    $this->actingAs($manager);

    Livewire::test(ListLeads::class)->assertCanSeeTableRecords($leads);
});

it('filters the lead list by tab', function () {
    $this->actingAs(User::factory()->admin()->create());
    $new = LeadFactory::new()->create();
    $lost = LeadFactory::new()->status(LeadStatus::Lost)->create(['lost_reason' => 'Budget']);

    Livewire::test(ListLeads::class)
        ->set('activeTab', 'lost')
        ->assertCanSeeTableRecords([$lost])
        ->assertCanNotSeeTableRecords([$new]);
});

it('prevents sales staff moving a colleague\'s lead on the pipeline', function () {
    $sales = User::factory()->sales()->create();
    $mine = LeadFactory::new()->ownedBy($sales)->create();
    $theirs = LeadFactory::new()->ownedBy(User::factory()->sales()->create())->create();

    $this->actingAs($sales);

    Livewire::test(LeadPipeline::class)
        ->call('moveLead', $mine->id, LeadStatus::Qualified->value, [$mine->id])
        ->call('moveLead', $theirs->id, LeadStatus::Qualified->value, [$theirs->id]);

    expect($mine->fresh()->status)->toBe(LeadStatus::Qualified)
        ->and($theirs->fresh()->status)->toBe(LeadStatus::New);
});
