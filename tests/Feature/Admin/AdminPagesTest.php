<?php

use App\Filament\Pages\LeadPipeline;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\CrmTasks\CrmTaskResource;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\Difficulties\DifficultyResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Tiers\TierResource;
use App\Filament\Resources\TripDepartures\TripDepartureResource;
use App\Filament\Resources\Trips\TripResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\LeadsBySourceChart;
use App\Filament\Widgets\LeadsTrendChart;
use App\Filament\Widgets\MyOpenTasks;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\UpcomingDepartures;
use App\Models\CrmTask;
use App\Models\TripDeparture;
use App\Models\User;
use Database\Factories\ActivityFactory;
use Database\Factories\BookingFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ContactFactory;
use Database\Factories\CrmTaskFactory;
use Database\Factories\DestinationFactory;
use Database\Factories\DifficultyFactory;
use Database\Factories\LeadFactory;
use Database\Factories\NoteFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\TagFactory;
use Database\Factories\TierFactory;
use Database\Factories\TravellerFactory;
use Database\Factories\TripDepartureFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

/** @return array<class-string<resource>, Closure(User): Model> */
function adminResourceRecords(): array
{
    return [
        TripResource::class => function () {
            $trip = TripFactory::new()->create(['destination_id' => DestinationFactory::new(), 'difficulty_id' => DifficultyFactory::new()]);
            $trip->days()->create(['day_number' => 1, 'title' => 'Arrive in Kathmandu', 'meals' => ['dinner']]);
            $trip->faqs()->create(['question' => 'Do I need a permit?', 'answer' => '<p>Yes.</p>']);
            $trip->prices()->create(['pax_min' => 1, 'pax_max' => 4, 'price' => 1500]);
            TripDepartureFactory::new()->create(['trip_id' => $trip->id]);

            return $trip;
        },
        TripDepartureResource::class => fn () => TripDepartureFactory::new()->create(),
        DestinationResource::class => fn () => DestinationFactory::new()->childOf(DestinationFactory::new()->create())->create(),
        ActivityResource::class => fn () => ActivityFactory::new()->create(),
        DifficultyResource::class => fn () => DifficultyFactory::new()->create(),
        TierResource::class => fn () => TierFactory::new()->create(),
        TagResource::class => fn () => TagFactory::new()->create(),
        LeadResource::class => function (User $admin) {
            $lead = LeadFactory::new()->forTrip()->ownedBy($admin)->create(['next_follow_up_at' => now()->subDay(), 'utm' => ['utm_source' => 'google']]);
            NoteFactory::new()->create(['notable_id' => $lead->id, 'user_id' => $admin->id]);
            CrmTaskFactory::new()->forLead($lead)->assignedTo($admin)->overdue()->create();
            QuoteFactory::new()->withItems()->create(['lead_id' => $lead->id, 'contact_id' => $lead->contact_id]);

            return $lead;
        },
        ContactResource::class => function () {
            $contact = ContactFactory::new()->create();
            LeadFactory::new()->create(['contact_id' => $contact->id]);
            BookingFactory::new()->create(['contact_id' => $contact->id]);

            return $contact;
        },
        CompanyResource::class => function () {
            $company = CompanyFactory::new()->create();
            ContactFactory::new()->create(['company_id' => $company->id]);

            return $company;
        },
        CrmTaskResource::class => fn (User $admin) => CrmTaskFactory::new()->forLead(LeadFactory::new()->create())->assignedTo($admin)->create(),
        QuoteResource::class => fn () => QuoteFactory::new()->withItems(2)->create(['trip_id' => TripFactory::new()]),
        BookingResource::class => function () {
            $booking = BookingFactory::new()->create(['trip_id' => TripFactory::new()]);
            TravellerFactory::new()->create(['booking_id' => $booking->id, 'is_lead' => true]);
            PaymentFactory::new()->forBooking($booking)->create();

            return $booking;
        },
        PaymentResource::class => fn () => PaymentFactory::new()->create(),
        UserResource::class => fn () => User::factory()->sales()->create(),
    ];
}

it('lets an admin load every page of every resource', function (string $resource) {
    /** @var class-string<resource> $resource */
    $record = adminResourceRecords()[$resource]($this->admin);
    $this->actingAs($this->admin);

    foreach (array_keys($resource::getPages()) as $page) {
        $url = in_array($page, ['index', 'create'], true)
            ? $resource::getUrl($page)
            : $resource::getUrl($page, ['record' => $record]);

        $this->get($url)->assertOk();
    }
})->with(array_keys(adminResourceRecords()));

it('renders the index page of every registered resource and custom page for an admin', function () {
    $this->actingAs($this->admin);

    $panel = Filament::getPanel('admin');

    foreach ($panel->getResources() as $resource) {
        $this->get($resource::getUrl('index'))->assertOk();
    }

    foreach ($panel->getPages() as $page) {
        $this->get($page::getUrl())->assertOk();
    }
});

it('renders the dashboard widgets with data', function () {
    LeadFactory::new()->count(3)->create(['source' => 'google']);
    LeadFactory::new()->create(['source' => null, 'created_at' => now()->subWeeks(3)]);
    TripDepartureFactory::new()->create(['start_date' => now()->addDays(10)]);
    CrmTaskFactory::new()->assignedTo($this->admin)->create();
    PaymentFactory::new()->paid()->create(['amount' => 1234]);
    BookingFactory::new()->confirmed()->create();

    $this->actingAs($this->admin)->get('/admin')->assertOk();

    Livewire::test(StatsOverview::class)->assertSee('New leads this week')->assertSee('Open pipeline')->assertSee('Received this month');
    Livewire::test(LeadsTrendChart::class)->assertOk();
    Livewire::test(LeadsBySourceChart::class)->assertOk();
    Livewire::test(UpcomingDepartures::class)->assertCanSeeTableRecords(TripDeparture::all());
    Livewire::test(MyOpenTasks::class)->assertCanSeeTableRecords(CrmTask::all());
});

it('renders the lead pipeline board', function () {
    $lead = LeadFactory::new()->forTrip()->create(['estimated_value' => 2500]);

    $this->actingAs($this->admin)
        ->get(LeadPipeline::getUrl())
        ->assertOk()
        ->assertSee($lead->reference);
});
