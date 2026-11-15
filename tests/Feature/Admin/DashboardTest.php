<?php

use App\Enums\BookingStatus;
use App\Enums\DepartureStatus;
use App\Enums\LeadStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\LeadsBySourceChart;
use App\Filament\Widgets\LeadsTrendChart;
use App\Filament\Widgets\RecentEnquiries;
use App\Filament\Widgets\RevenueOverview;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TodayOverview;
use App\Filament\Widgets\UpcomingDepartures;
use App\Models\User;
use Database\Factories\BookingFactory;
use Database\Factories\CrmTaskFactory;
use Database\Factories\LeadFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\TripDepartureFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('greets an admin by first name and shows every quick action and widget', function () {
    $this->travelTo(Carbon::parse('2026-11-16 09:30', 'Asia/Kathmandu'));
    $admin = User::factory()->admin()->create(['name' => 'Pema Sherpa']);

    $this->actingAs($admin)
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSee('Good morning, Pema')
        ->assertSee('Monday 16 November 2026')
        ->assertSee(['New lead', 'New quote', 'New trip', 'View site'])
        ->assertSeeLivewire(TodayOverview::class)
        ->assertSeeLivewire(StatsOverview::class)
        ->assertSeeLivewire(RevenueOverview::class)
        ->assertSeeLivewire(RecentEnquiries::class)
        ->assertSeeLivewire(LeadsTrendChart::class)
        ->assertSeeLivewire(LeadsBySourceChart::class);
});

it('picks the greeting from the time of day in Kathmandu', function (string $time, string $greeting) {
    expect(Dashboard::greeting(Carbon::parse($time, 'Asia/Kathmandu')))->toBe($greeting);
})->with([
    ['2026-11-16 06:00', 'Good morning'],
    ['2026-11-16 13:00', 'Good afternoon'],
    ['2026-11-16 19:00', 'Good evening'],
    ['2026-11-16 02:00', 'Good evening'],
]);

it('shows sales staff the CRM widgets and revenue but no catalogue action', function () {
    $this->actingAs(User::factory()->sales()->create())
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSee(['New lead', 'New quote', 'View site'])
        ->assertDontSee('New trip')
        ->assertSeeLivewire(TodayOverview::class)
        ->assertSeeLivewire(RevenueOverview::class)
        ->assertSeeLivewire(RecentEnquiries::class);
});

it('shows content editors departures only, without CRM or revenue widgets', function () {
    TripDepartureFactory::new()->create(['start_date' => today()->addDays(2)]);

    $this->actingAs(User::factory()->role('content')->create())
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSee(['New trip', 'View site'])
        ->assertDontSee(['New lead', 'New quote'])
        ->assertSeeLivewire(TodayOverview::class)
        ->assertDontSeeLivewire(StatsOverview::class)
        ->assertDontSeeLivewire(RevenueOverview::class)
        ->assertDontSeeLivewire(RecentEnquiries::class);

    Livewire::test(TodayOverview::class)
        ->assertSee('Departing in the next 7 days')
        ->assertDontSee('Follow-ups due')
        ->assertDontSee('Balances due');
});

it('lists what is due today for a sales user, leaving out colleagues and later items', function () {
    $sales = User::factory()->sales()->create();
    $colleague = User::factory()->sales()->create();

    LeadFactory::new()->ownedBy($sales)->create(['reference' => 'WH-MINE-1', 'next_follow_up_at' => now()->subDays(2)]);
    LeadFactory::new()->ownedBy($colleague)->create(['reference' => 'WH-THEIRS-1', 'next_follow_up_at' => now()->subDay()]);
    LeadFactory::new()->ownedBy($sales)->create(['reference' => 'WH-LATER-1', 'next_follow_up_at' => now()->addDays(3)]);
    LeadFactory::new()->ownedBy($sales)->status(LeadStatus::Lost)->create(['reference' => 'WH-LOST-1', 'next_follow_up_at' => now()->subDay()]);

    CrmTaskFactory::new()->assignedTo($sales)->overdue()->create(['title' => 'Call back about permits']);
    CrmTaskFactory::new()->assignedTo($colleague)->overdue()->create(['title' => 'Colleague task']);
    CrmTaskFactory::new()->assignedTo($sales)->create(['title' => 'Next week task', 'due_at' => now()->addWeek()]);

    $soon = TripFactory::new()->create(['title' => 'Everest Base Camp Trek']);
    $later = TripFactory::new()->create(['title' => 'Manaslu Circuit']);
    TripDepartureFactory::new()->create(['trip_id' => $soon->id, 'start_date' => today()->addDays(3)]);
    TripDepartureFactory::new()->create(['trip_id' => $later->id, 'start_date' => today()->addDays(20)]);
    TripDepartureFactory::new()->create(['start_date' => today()->addDays(2), 'status' => DepartureStatus::Cancelled]);

    $overdue = BookingFactory::new()->create(['status' => BookingStatus::Confirmed, 'balance_due_on' => today()->subDays(3), 'total' => 3000, 'amount_paid' => 600]);
    $dueSoon = BookingFactory::new()->create(['status' => BookingStatus::Confirmed, 'balance_due_on' => today()->addDays(10), 'total' => 2000, 'amount_paid' => 500]);
    $paid = BookingFactory::new()->create(['status' => BookingStatus::PaidInFull, 'balance_due_on' => today()->addDays(5), 'total' => 2000, 'amount_paid' => 2000]);
    $farOff = BookingFactory::new()->create(['status' => BookingStatus::Confirmed, 'balance_due_on' => today()->addDays(40)]);

    $this->actingAs($sales);

    Livewire::test(TodayOverview::class)
        ->assertSee(['My tasks due', 'WH-MINE-1', 'Call back about permits', 'Everest Base Camp Trek', $overdue->number, '$2,400.00', $dueSoon->number, '$1,500.00'])
        ->assertSee('Overdue since')
        ->assertDontSee(['WH-THEIRS-1', 'WH-LATER-1', 'WH-LOST-1', 'Colleague task', 'Next week task', 'Manaslu Circuit', $paid->number, $farOff->number]);
});

it('shows a manager every follow-up and task due', function () {
    $sales = User::factory()->sales()->create();
    LeadFactory::new()->ownedBy($sales)->create(['reference' => 'WH-TEAM-1', 'next_follow_up_at' => now()->subHour()]);
    CrmTaskFactory::new()->assignedTo($sales)->overdue()->create(['title' => 'Team task']);

    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(TodayOverview::class)->assertSee(['Tasks due', 'WH-TEAM-1', 'Team task']);
});

it('reads well when nothing is due', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TodayOverview::class)->assertSee([
        'No lead follow-ups due today.',
        'Nothing due today. Enjoy the quiet.',
        'No departures this week.',
        'No balances due in the next 14 days.',
    ]);

    Livewire::test(RevenueOverview::class)->assertSee('No payments received this month or last');
    Livewire::test(RecentEnquiries::class)->assertSee('No enquiries yet');
});

it('marks a task as done from the today panel', function () {
    $sales = User::factory()->sales()->create();
    $task = CrmTaskFactory::new()->assignedTo($sales)->overdue()->create();

    $this->actingAs($sales);

    Livewire::test(TodayOverview::class)->call('completeTask', $task->id)->assertNotified('Task completed');

    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('does not let a content editor complete CRM tasks', function () {
    $task = CrmTaskFactory::new()->overdue()->create();

    $this->actingAs(User::factory()->role('content')->create());

    Livewire::test(TodayOverview::class)->call('completeTask', $task->id)->assertForbidden();

    expect($task->fresh()->completed_at)->toBeNull();
});

it('compares paid payments this month with last month per currency', function () {
    $this->travelTo(Carbon::parse('2026-11-20 12:00', 'Asia/Kathmandu'));

    PaymentFactory::new()->paid()->create(['amount' => 1500, 'currency' => 'USD', 'paid_at' => now()->subDays(5)]);
    PaymentFactory::new()->paid()->create(['amount' => 500, 'currency' => 'USD', 'paid_at' => now()->subDays(1)]);
    PaymentFactory::new()->paid()->create(['amount' => 1000, 'currency' => 'USD', 'paid_at' => now()->subMonthNoOverflow()]);
    PaymentFactory::new()->paid()->create(['amount' => 800, 'currency' => 'EUR', 'paid_at' => now()->subDays(2)]);
    PaymentFactory::new()->create(['amount' => 9999, 'currency' => 'USD']);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(RevenueOverview::class)
        ->assertSee(['Received this month · USD', '$2,000.00', '+100% vs $1,000.00 last month'])
        ->assertSee(['Received this month · EUR', '€800.00', 'Nothing received last month'])
        ->assertDontSee('9,999');
});

it('lists recent enquiries linked to the lead', function () {
    $lead = LeadFactory::new()->forTrip()->create(['source' => 'google']);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(RecentEnquiries::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertSee([$lead->reference, $lead->contact->name, $lead->trip->title, 'Google']);
});

it('lists upcoming departures for the next 60 days by default, switchable to 30 or 90', function () {
    $admin = User::factory()->admin()->create();
    $in10 = TripDepartureFactory::new()->create(['start_date' => today()->addDays(10), 'end_date' => today()->addDays(20)]);
    $in45 = TripDepartureFactory::new()->create(['start_date' => today()->addDays(45), 'end_date' => today()->addDays(55)]);
    $in80 = TripDepartureFactory::new()->create(['start_date' => today()->addDays(80), 'end_date' => today()->addDays(90)]);
    $cancelled = TripDepartureFactory::new()->create(['start_date' => today()->addDays(12), 'end_date' => today()->addDays(22), 'status' => DepartureStatus::Cancelled]);
    $past = TripDepartureFactory::new()->create(['start_date' => today()->subDays(3), 'end_date' => today()->addDays(7)]);

    $this->actingAs($admin);

    Livewire::test(UpcomingDepartures::class)
        ->assertCanSeeTableRecords([$in10, $in45])
        ->assertCanNotSeeTableRecords([$in80, $cancelled, $past])
        ->filterTable('range', '30')
        ->assertCanSeeTableRecords([$in10])
        ->assertCanNotSeeTableRecords([$in45])
        ->filterTable('range', '90')
        ->assertCanSeeTableRecords([$in10, $in45, $in80]);
});

it('shows upcoming departures to content staff too', function () {
    $this->actingAs(User::factory()->role('content')->create())
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSeeLivewire(UpcomingDepartures::class);
});
