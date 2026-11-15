<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Trips\Pages\ListTrips;
use App\Models\User;
use App\Support\Permissions;
use Database\Factories\BookingFactory;
use Database\Factories\LeadFactory;
use Database\Factories\PageFactory;
use Database\Factories\PostFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/** @return array<string, mixed> tab key => badge */
function tabBadges(string $page): array
{
    return collect(Livewire::test($page)->instance()->getCachedTabs())
        ->map(fn ($tab) => $tab->getBadge())
        ->all();
}

it('splits leads into my open leads, follow-ups due, won and lost', function () {
    $this->freezeTime();
    $sales = User::factory()->sales()->create();
    $myOpen = LeadFactory::new()->ownedBy($sales)->status(LeadStatus::Qualified)->create();
    $myLost = LeadFactory::new()->ownedBy($sales)->status(LeadStatus::Lost)->create(['lost_reason' => 'Budget']);
    $myTravelled = LeadFactory::new()->ownedBy($sales)->status(LeadStatus::Travelled)->create();
    $new = LeadFactory::new()->ownedBy(null)->create();
    $dueFollowUp = LeadFactory::new()->ownedBy(null)->status(LeadStatus::Contacted)->create(['next_follow_up_at' => now()->subHour()]);
    $laterFollowUp = LeadFactory::new()->ownedBy(null)->status(LeadStatus::Contacted)->create(['next_follow_up_at' => now()->addDay()]);
    $won = LeadFactory::new()->ownedBy(null)->status(LeadStatus::DepositPaid)->create();
    $this->actingAs($sales);

    Livewire::test(ListLeads::class, ['activeTab' => 'mine'])
        ->assertCanSeeTableRecords([$myOpen])
        ->assertCanNotSeeTableRecords([$myLost, $myTravelled, $new]);
    Livewire::test(ListLeads::class, ['activeTab' => 'follow_up'])
        ->assertCanSeeTableRecords([$dueFollowUp])
        ->assertCanNotSeeTableRecords([$laterFollowUp, $myOpen]);
    Livewire::test(ListLeads::class, ['activeTab' => 'won'])
        ->assertCanSeeTableRecords([$won, $myTravelled])
        ->assertCanNotSeeTableRecords([$myOpen, $new]);

    expect(tabBadges(ListLeads::class))->toMatchArray([
        'all' => null,
        'mine' => 1,
        'new' => 1,
        'follow_up' => 1,
        'won' => 2,
        'lost' => 1,
    ]);
});

it('counts only the leads a sales user may see in the tab badges', function () {
    $sales = User::factory()->sales()->create();
    LeadFactory::new()->ownedBy(User::factory()->sales()->create())->create();
    LeadFactory::new()->ownedBy(null)->create();
    $this->actingAs($sales);

    expect(tabBadges(ListLeads::class)['new'])->toEqual(1);
});

it('hides the my open leads tab from users who cannot work leads', function () {
    $viewer = User::factory()->create()->givePermissionTo(Permissions::for('leads', Permissions::VIEW));
    $this->actingAs($viewer);

    expect(tabBadges(ListLeads::class))->not->toHaveKey('mine')->toHaveKey('new');
});

it('splits bookings into upcoming, departing soon, balance due and past', function () {
    $this->freezeTime();
    $this->actingAs(User::factory()->admin()->create());
    $soon = BookingFactory::new()->create(['start_date' => today()->addDays(10), 'total' => 3000, 'amount_paid' => 600]);
    $later = BookingFactory::new()->create(['start_date' => today()->addDays(90), 'total' => 3000, 'amount_paid' => 3000]);
    $cancelledSoon = BookingFactory::new()->create(['start_date' => today()->addDays(5), 'status' => BookingStatus::Cancelled, 'amount_paid' => 0]);
    $past = BookingFactory::new()->create(['start_date' => today()->subDays(20), 'total' => 3000, 'amount_paid' => 3000]);

    Livewire::test(ListBookings::class)
        ->assertSet('activeTab', 'upcoming')
        ->assertCanSeeTableRecords([$soon, $later, $cancelledSoon])
        ->assertCanNotSeeTableRecords([$past]);
    Livewire::test(ListBookings::class, ['activeTab' => 'departing_soon'])
        ->assertCanSeeTableRecords([$soon])
        ->assertCanNotSeeTableRecords([$later, $cancelledSoon, $past]);
    Livewire::test(ListBookings::class, ['activeTab' => 'balance_due'])
        ->assertCanSeeTableRecords([$soon])
        ->assertCanNotSeeTableRecords([$later, $cancelledSoon, $past]);
    Livewire::test(ListBookings::class, ['activeTab' => 'past'])
        ->assertCanSeeTableRecords([$past])
        ->assertCanNotSeeTableRecords([$soon, $later]);

    expect(tabBadges(ListBookings::class))->toMatchArray([
        'upcoming' => 3,
        'departing_soon' => 1,
        'balance_due' => 1,
        'past' => 1,
    ]);
});

it('splits trips into published, drafts and fixed departures', function () {
    $this->actingAs(User::factory()->admin()->create());
    $published = TripFactory::new()->create();
    $draft = TripFactory::new()->draft()->create();
    $fixed = TripFactory::new()->fixedDeparture()->create();

    Livewire::test(ListTrips::class, ['activeTab' => 'drafts'])
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$published, $fixed]);
    Livewire::test(ListTrips::class, ['activeTab' => 'fixed'])
        ->assertCanSeeTableRecords([$fixed])
        ->assertCanNotSeeTableRecords([$published, $draft]);

    expect(tabBadges(ListTrips::class))->toMatchArray(['published' => 2, 'drafts' => 1, 'fixed' => 1]);
});

it('splits posts and pages into published, scheduled and drafts', function (string $page, Closure $factory) {
    $this->actingAs(User::factory()->admin()->create());
    $published = $factory()->create(['published_at' => now()->subDay()]);
    $scheduled = $factory()->create(['published_at' => now()->addWeek()]);
    $draft = $factory()->draft()->create();

    Livewire::test($page, ['activeTab' => 'published'])
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords([$scheduled, $draft]);
    Livewire::test($page, ['activeTab' => 'scheduled'])
        ->assertCanSeeTableRecords([$scheduled])
        ->assertCanNotSeeTableRecords([$published, $draft]);
    Livewire::test($page, ['activeTab' => 'drafts'])
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$published, $scheduled]);

    expect(tabBadges($page))->toMatchArray(['published' => 1, 'scheduled' => 1, 'drafts' => 1]);
})->with([
    'posts' => [ListPosts::class, fn () => PostFactory::new()],
    'pages' => [ListPages::class, fn () => PageFactory::new()],
]);
