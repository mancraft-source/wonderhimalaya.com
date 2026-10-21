<?php

use App\Actions\Leads\SubmitEnquiry;
use App\Enums\PublishStatus;
use App\Events\LeadCreated;
use App\Livewire\TripReviewForm;
use App\Models\Testimonial;
use App\Models\Tier;
use App\Models\Trip;
use App\Support\Content;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

function pricedTrip(array $attrs = []): Trip
{
    $standard = Tier::firstOrCreate(['slug' => 'standard'], ['name' => 'Standard', 'sort' => 1]);
    $trip = Trip::create($attrs + [
        'title' => 'Langtang Valley Trek', 'slug' => 'langtang-valley-trek', 'status' => PublishStatus::Published,
        'published_at' => now()->subDay(), 'duration_days' => 7, 'price_from' => 700, 'max_altitude_m' => 3870,
    ]);
    foreach ([[1, 1, 700], [2, 5, 620], [6, null, 560]] as $i => [$min, $max, $price]) {
        $trip->prices()->create(['tier_id' => $standard->id, 'pax_min' => $min, 'pax_max' => $max, 'price' => $price, 'sort' => $i]);
    }

    return $trip;
}

it('picks the per-person price for the group size', function (int $pax, float $expected) {
    expect(pricedTrip()->priceFor($pax))->toBe($expected);
})->with([[1, 700.0], [3, 620.0], [5, 620.0], [6, 560.0], [14, 560.0]]);

it('builds a readable group pricing matrix', function () {
    $bands = pricedTrip()->load('prices.tier')->pricingMatrix()->first()['bands'];

    expect(collect($bands)->pluck('label')->all())->toBe(['1 person', '2–5 people', '6+ people']);
});

it('estimates lead value from the matching group band', function () {
    Event::fake([LeadCreated::class]);
    $trip = pricedTrip();

    $lead = app(SubmitEnquiry::class)->handle(['type' => 'private_trip', 'first_name' => 'Ola', 'email' => 'ola@example.com', 'trip_id' => $trip->id, 'adults' => 4]);

    expect((float) $lead->estimated_value)->toBe(2480.0);
});

it('shows the group price table on the trip page', function () {
    pricedTrip();

    $this->get('/trip/langtang-valley-trek')->assertOk()
        ->assertSee('Group price calculator')
        ->assertSee('2–5 people')
        ->assertSee('US$620');
});

it('stores traveller reviews unpublished until approved', function () {
    $trip = pricedTrip();

    Livewire::test(TripReviewForm::class, ['trip' => $trip])
        ->set('rating', 4)->set('body', 'Wonderful guides, great tea-houses and stunning Langtang views every day.')
        ->set('author_name', 'Priya')->set('author_email', 'Priya@Example.com')->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $review = Testimonial::first();
    expect($review->trip_id)->toBe($trip->id)
        ->and($review->is_published)->toBeFalse()
        ->and($review->source)->toBe('website')
        ->and($review->author_email)->toBe('priya@example.com');

    $this->get('/trip/langtang-valley-trek')->assertDontSee('Wonderful guides, great tea-houses');
});

it('shows approved trip reviews with their average rating', function () {
    $trip = pricedTrip();
    Testimonial::create(['trip_id' => $trip->id, 'author_name' => 'Ken', 'body' => 'Kyanjin Gompa sunrise was magic.', 'rating' => 5, 'source' => 'google', 'is_published' => true]);
    Testimonial::create(['trip_id' => $trip->id, 'author_name' => 'Lia', 'body' => 'Great pace and a lovely guide.', 'rating' => 4, 'source' => 'manual', 'is_published' => true]);

    $this->get('/trip/langtang-valley-trek')->assertOk()
        ->assertSee('Reviews of this trip')->assertSee('4.5')->assertSee('Kyanjin Gompa sunrise was magic.');
});

it('places the trip video above the FAQs and supports vimeo', function () {
    $trip = pricedTrip(['videos' => ['https://vimeo.com/123456789']]);
    $trip->faqs()->create(['question' => 'Do I need a permit?', 'answer' => 'Yes.', 'sort' => 1]);

    $this->get('/trip/langtang-valley-trek')->assertOk()
        ->assertSeeInOrder(['See the trail', 'Do I need a permit?'])
        ->assertSee('player.vimeo.com/video/123456789', false);
});

it('uses a trip-specific packing list and note when set', function () {
    $trip = pricedTrip(['packing' => [['group' => 'Langtang essentials', 'items' => ['Microspikes in winter']]], 'itinerary_note' => 'Kyanjin day hike depends on weather.']);
    $trip->days()->create(['day_number' => 1, 'title' => 'Drive to Syabrubesi', 'sort' => 1]);

    $this->get('/trip/langtang-valley-trek')->assertOk()
        ->assertSee('Langtang essentials')->assertSee('Microspikes in winter')
        ->assertSee('Kyanjin day hike depends on weather.')
        ->assertDontSee('Head &amp; upper body', false);
});

it('stores only content that differs from the defaults', function () {
    $defaults = ['title' => 'Hello', 'tiles' => [['t' => 'a']], 'nested' => ['x' => '1', 'y' => '2']];

    expect(Content::overrides(['title' => 'Hello', 'tiles' => [['t' => 'a']], 'nested' => ['x' => '1', 'y' => '3']], $defaults))
        ->toBe(['nested' => ['y' => '3']])
        ->and(Content::merge($defaults, ['title' => '', 'nested' => ['y' => '3']]))
        ->toBe(['title' => 'Hello', 'tiles' => [['t' => 'a']], 'nested' => ['x' => '1', 'y' => '3']]);
});
