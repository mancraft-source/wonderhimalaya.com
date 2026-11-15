<?php

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\Trips\TripResource;
use App\Models\User;
use Database\Factories\BookingFactory;
use Database\Factories\ContactFactory;
use Database\Factories\DestinationFactory;
use Database\Factories\LeadFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\GlobalSearch\GlobalSearchResult;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/** @return array<int, string> */
function searchTitles(string $resource, string $search): array
{
    return $resource::getGlobalSearchResults($search)
        ->map(fn (GlobalSearchResult $result): string => (string) $result->title)
        ->values()
        ->all();
}

it('finds leads by reference and by their contact', function (string $search) {
    $this->actingAs(User::factory()->admin()->create());
    $contact = ContactFactory::new()->create([
        'first_name' => 'Pemba', 'last_name' => 'Sherpa', 'email' => 'pemba@example.com',
        'phone' => '+977 981-234 5678', 'whatsapp' => '+44 7700 900123',
    ]);
    LeadFactory::new()->for($contact)->create(['reference' => 'WH-L-4242']);
    LeadFactory::new()->create();

    expect(searchTitles(LeadResource::class, $search))->toBe(['WH-L-4242 — Pemba Sherpa']);
})->with([
    'reference' => 'L-4242',
    'email' => 'pemba@example',
    'full name' => 'Pemba Sherpa',
    'phone without spaces' => '9779812345678',
    'phone with other spacing' => '+977 98123 45678',
    'whatsapp' => '447700900123',
]);

it('previews a lead with its type, trip, status and age', function () {
    $this->actingAs(User::factory()->admin()->create());
    $trip = TripFactory::new()->create(['title' => 'Everest Base Camp Trek']);
    $lead = LeadFactory::new()->forTrip($trip)->status(LeadStatus::New)->create(['type' => LeadType::TailorMade, 'created_at' => now()->subDays(2)]);

    $result = LeadResource::getGlobalSearchResults($lead->reference)->sole();

    expect($result->details)->toBe(['Tailor-made · Everest Base Camp Trek · New · 2 days ago'])
        ->and($result->url)->toBe(LeadResource::getUrl('view', ['record' => $lead]));
});

it('only lets sales staff find their own and unassigned leads', function () {
    $sales = User::factory()->sales()->create();
    $contact = ContactFactory::new()->create(['last_name' => 'Tamang']);
    LeadFactory::new()->for($contact)->ownedBy($sales)->create(['reference' => 'WH-MINE']);
    LeadFactory::new()->for($contact)->ownedBy(null)->create(['reference' => 'WH-OPEN']);
    LeadFactory::new()->for($contact)->ownedBy(User::factory()->sales()->create())->create(['reference' => 'WH-THEIRS']);

    $this->actingAs($sales);

    expect(collect(searchTitles(LeadResource::class, 'Tamang'))->map(fn (string $title) => strtok($title, ' '))->sort()->values()->all())
        ->toBe(['WH-MINE', 'WH-OPEN']);
});

it('hides CRM records from content editors', function () {
    ContactFactory::new()->create(['email' => 'hidden@example.com']);
    $this->actingAs(User::factory()->role('content')->create());

    expect(LeadResource::canGloballySearch())->toBeFalse()
        ->and(ContactResource::canGloballySearch())->toBeFalse()
        ->and(BookingResource::canGloballySearch())->toBeFalse();
});

it('finds contacts by name, email, phone and whatsapp', function (string $search) {
    $this->actingAs(User::factory()->sales()->create());
    ContactFactory::new()->create([
        'first_name' => 'Ang', 'last_name' => 'Dorje', 'email' => 'ang@example.com',
        'phone' => '+977-1-4412345', 'whatsapp' => '+977 980 0000111',
    ]);
    ContactFactory::new()->create(['first_name' => 'Someone', 'last_name' => 'Else']);

    expect(searchTitles(ContactResource::class, $search))->toBe(['Ang Dorje']);
})->with(['Ang Dorje', 'ang@example', '97714412345', '980 0000 111']);

it('finds bookings by number and contact, previewing trip, date and balance', function (string $search) {
    $this->actingAs(User::factory()->admin()->create());
    $contact = ContactFactory::new()->create(['first_name' => 'Lhakpa', 'last_name' => 'Rai', 'email' => 'lhakpa@example.com', 'phone' => '+977 9800011122']);
    $trip = TripFactory::new()->create(['title' => 'Annapurna Circuit']);
    BookingFactory::new()->for($contact)->for($trip)->create([
        'number' => 'WH-B-7001', 'start_date' => '2027-03-12', 'total' => 2000, 'amount_paid' => 800, 'currency' => 'USD',
    ]);
    BookingFactory::new()->create();

    $result = BookingResource::getGlobalSearchResults($search)->sole();

    expect($result->title)->toBe('WH-B-7001 — Lhakpa Rai')
        ->and($result->details[0])->toStartWith('Annapurna Circuit · 12 Mar 2027 · Balance USD 1,200');
})->with(['B-7001', 'Lhakpa Rai', 'lhakpa@example', '9800011122']);

it('finds quotes by number, title and contact', function (string $search) {
    $this->actingAs(User::factory()->admin()->create());
    $contact = ContactFactory::new()->create(['first_name' => 'Mingma', 'last_name' => 'Lama']);
    QuoteFactory::new()->for($contact)->create(['number' => 'WH-Q-3003', 'title' => 'Manaslu private trek']);
    QuoteFactory::new()->create(['title' => 'Something else']);

    expect(searchTitles(QuoteResource::class, $search))->toBe(['WH-Q-3003 — Mingma Lama']);
})->with(['Q-3003', 'Manaslu', 'Mingma Lama']);

it('finds trips by title, code and slug, previewing region, days, price and status', function (string $search) {
    $this->actingAs(User::factory()->role('content')->create());
    $destination = DestinationFactory::new()->create(['name' => 'Everest']);
    TripFactory::new()->draft()->for($destination)->create([
        'title' => 'Gokyo Lakes Trek', 'trip_code' => 'GKY-12', 'slug' => 'gokyo-lakes-trek',
        'duration_days' => 12, 'price_from' => 1450, 'sale_price' => null, 'currency' => 'USD',
    ]);
    TripFactory::new()->create(['title' => 'Other trip', 'trip_code' => 'OTH-01', 'slug' => 'other-trip']);

    $result = TripResource::getGlobalSearchResults($search)->sole();

    expect($result->title)->toBe('Gokyo Lakes Trek (GKY-12)')
        ->and($result->details)->toBe(['Everest · 12 days · from USD 1,450 · Draft']);
})->with(['Gokyo Lakes', 'GKY-12', 'gokyo-lakes']);
