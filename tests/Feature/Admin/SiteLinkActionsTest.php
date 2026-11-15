<?php

use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\Destinations\Pages\EditDestination;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Filament\Resources\Trips\Pages\EditTrip;
use App\Models\User;
use App\Support\Preview;
use Database\Factories\ActivityFactory;
use Database\Factories\DestinationFactory;
use Database\Factories\PageFactory;
use Database\Factories\PostFactory;
use Database\Factories\TeamMemberFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('admin'));
});

it('links a published record to its public URL and hides the preview', function (string $page, Closure $makeRecord) {
    $record = $makeRecord();

    Livewire::test($page, ['record' => $record->getRouteKey()])
        ->assertActionVisible('view_on_site')
        ->assertActionHasUrl('view_on_site', url($record->publicPath()))
        ->assertActionShouldOpenUrlInNewTab('view_on_site')
        ->assertActionHidden('preview');
})->with([
    'trip' => [EditTrip::class, fn () => TripFactory::new()->create()],
    'page' => [EditPage::class, fn () => PageFactory::new()->create()],
    'post' => [EditPost::class, fn () => PostFactory::new()->create()],
    'team member' => [EditTeamMember::class, fn () => TeamMemberFactory::new()->create()],
    'destination' => [EditDestination::class, fn () => DestinationFactory::new()->create()],
    'activity' => [EditActivity::class, fn () => ActivityFactory::new()->create()],
]);

it('offers a signed preview instead of View on site for an unpublished record', function (string $page, Closure $makeRecord) {
    $this->freezeTime();
    $record = $makeRecord();

    Livewire::test($page, ['record' => $record->getRouteKey()])
        ->assertActionHidden('view_on_site')
        ->assertActionVisible('preview')
        ->assertActionShouldOpenUrlInNewTab('preview')
        ->assertActionHasUrl('preview', Preview::signedUrl($record));
})->with([
    'draft trip' => [EditTrip::class, fn () => TripFactory::new()->draft()->create()],
    'draft page' => [EditPage::class, fn () => PageFactory::new()->draft()->create()],
    'scheduled post' => [EditPost::class, fn () => PostFactory::new()->scheduled()->create()],
    'unpublished team member' => [EditTeamMember::class, fn () => TeamMemberFactory::new()->unpublished()->create()],
]);

it('hides both site links for a trashed record', function () {
    $trip = TripFactory::new()->create();
    $trip->delete();

    Livewire::test(EditTrip::class, ['record' => $trip->getRouteKey()])
        ->assertActionHidden('view_on_site')
        ->assertActionHidden('preview');
});

it('shows the table site link only for live records', function () {
    $live = PostFactory::new()->create();
    $draft = PostFactory::new()->draft()->create();

    Livewire::test(ListPosts::class)
        ->assertTableActionVisible('view_on_site', $live)
        ->assertTableActionHidden('view_on_site', $draft);

    $guide = TeamMemberFactory::new()->create();
    $hiddenGuide = TeamMemberFactory::new()->unpublished()->create();

    Livewire::test(ListTeamMembers::class)
        ->assertTableActionVisible('open_on_site', $guide)
        ->assertTableActionHidden('open_on_site', $hiddenGuide);
});
