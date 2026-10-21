<?php

use App\Enums\PublishStatus;
use App\Livewire\TripEnquiry;
use App\Models\Lead;
use App\Models\Trip;
use App\Models\TripDay;
use App\Support\TripInclusions;
use Livewire\Livewire;

function tripPageTrip(): Trip
{
    $trip = Trip::create([
        'title' => 'Mardi Himal Trek',
        'slug' => 'mardi-himal-trek',
        'status' => PublishStatus::Published,
        'published_at' => now()->subDay(),
        'duration_days' => 8,
        'max_altitude_m' => 4500,
        'price_from' => 720,
        'includes' => ['Round-trip Kathmandu-Pokhara flights', 'Annapurna Conservation Area permit', 'Tea house accommodation during the trek'],
        'excludes' => ['Travel insurance and medical evacuation', 'Tipping'],
    ]);
    $trip->days()->create(['day_number' => 1, 'title' => 'Arrive in Kathmandu', 'sort' => 1]);
    $trip->days()->create(['day_number' => 2, 'title' => 'Trek to Forest Camp', 'altitude_m' => 2550, 'distance_km' => 8, 'duration' => '8 Km, 5-6 hrs trek', 'sort' => 2]);

    return $trip;
}

it('shows the reference itinerary sections on the trip page', function () {
    tripPageTrip();

    $this->get('/trip/mardi-himal-trek')
        ->assertOk()
        ->assertSeeInOrder(['At a glance', 'Dates &amp; prices', 'Price includes &amp; excludes', 'What to pack', 'Reserve your place'], false)
        ->assertSee('Transport &amp; flights', false)
        ->assertSee('Need help booking?')
        ->assertSee('/trip/mardi-himal-trek/itinerary.pdf', false);
});

it('downloads a printable itinerary pdf', function () {
    tripPageTrip();

    $this->get('/trip/mardi-himal-trek/itinerary.pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('mardi-himal-trek-itinerary.pdf');
});

it('does not serve itineraries for unpublished trips', function () {
    tripPageTrip()->update(['status' => PublishStatus::Draft]);

    $this->get('/trip/mardi-himal-trek/itinerary.pdf')->assertNotFound();
});

it('groups inclusions into categories', function () {
    $groups = collect(TripInclusions::group(['Airport pick-up and drop', 'TIMS card and park permit', 'Licensed English-speaking guide', 'Something unusual']))
        ->mapWithKeys(fn ($g) => [$g['title'] => $g['items']]);

    expect($groups->keys()->all())->toBe(['Transport & flights', 'Permits & fees', 'Guide & staff', 'Other'])
        ->and($groups['Other'])->toBe(['Something unusual']);
});

it('strips a leading distance from imported day durations', function (?string $raw, ?string $expected) {
    expect((new TripDay(['duration' => $raw]))->durationLabel())->toBe($expected);
})->with([
    ['8 Km, 3-4 hrs trek', '3-4 hrs trek'],
    ['5-6 hours', '5-6 hours'],
    ['12 km', null],
    [null, null],
]);

it('records how the traveller found us on trip enquiries', function () {
    $trip = tripPageTrip();

    Livewire::test(TripEnquiry::class, ['trip' => $trip])
        ->set('mode', 'private')
        ->set('first_name', 'Mia')->set('email', 'mia@example.com')->set('privacy_accepted', true)
        ->set('heard_about', 'Tripadvisor')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Lead::first()->message)->toContain('Found us via: Tripadvisor');
});
