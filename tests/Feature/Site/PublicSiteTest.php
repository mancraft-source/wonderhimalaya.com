<?php

use App\Enums\LeadType;
use App\Enums\PublishStatus;
use App\Livewire\ContactForm;
use App\Livewire\PartnerForm;
use App\Livewire\TailorMadeBuilder;
use App\Livewire\TripEnquiry;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Support\UserAgent;
use Livewire\Livewire;

function siteTrip(array $attrs = []): Trip
{
    $nepal = Destination::firstOrCreate(['slug' => 'nepal'], ['name' => 'Nepal']);
    $region = Destination::firstOrCreate(['slug' => 'everest-region'], ['name' => 'Everest Region', 'parent_id' => $nepal->id]);

    $trip = Trip::create($attrs + [
        'title' => 'Everest Base Camp Trek',
        'slug' => 'everest-base-camp-trek',
        'status' => PublishStatus::Published,
        'published_at' => now()->subDay(),
        'duration_days' => 14,
        'price_from' => 1585,
        'destination_id' => $region->id,
        'excerpt' => 'The classic trek to the foot of the world.',
    ]);
    $trip->destinations()->sync([$nepal->id, $region->id]);
    foreach ([[1, 'Fly to Lukla', 2860], [2, 'Namche Bazaar', 3440], [3, 'Base Camp', 5364]] as [$n, $t, $alt]) {
        $trip->days()->create(['day_number' => $n, 'title' => $t, 'altitude_m' => $alt, 'sort' => $n]);
    }

    return $trip;
}

it('renders the main public pages', function (string $path) {
    siteTrip();
    $this->get($path)->assertOk();
})->with(['/', '/trips', '/fixed-departures', '/tailor-made', '/b2b', '/contact', '/our-team', '/blog', '/trip/everest-base-camp-trek', '/destination/everest-region', '/destination/nepal']);

it('shows trip details with structured data and altitude profile', function () {
    siteTrip();

    $this->get('/trip/everest-base-camp-trek')
        ->assertOk()
        ->assertSee('Everest Base Camp Trek')
        ->assertSee('TouristTrip', false)
        ->assertSee('Altitude profile')
        ->assertSee('US$1,585');
});

it('hides draft trips from the public', function () {
    siteTrip(['status' => PublishStatus::Draft, 'slug' => 'secret-trek', 'title' => 'Secret']);

    $this->get('/trip/secret-trek')->assertNotFound();
});

it('renders cms pages by nested path', function () {
    $guide = Page::create(['title' => 'Travel Guide', 'slug' => 'travel-guide', 'status' => PublishStatus::Published]);
    Page::create(['title' => 'Nepal Visa', 'slug' => 'nepal-visa', 'parent_id' => $guide->id, 'template' => 'guide', 'status' => PublishStatus::Published,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<p>Visa on arrival is available.</p>']]]]);

    $this->get('/travel-guide/nepal-visa')->assertOk()->assertSee('Visa on arrival is available.');
});

it('renders blog posts under /blog', function () {
    Post::create(['title' => 'Monasteries at the edge of the sky', 'slug' => 'monasteries', 'body' => '<p>Prayer wheels.</p>', 'status' => PublishStatus::Published, 'published_at' => now()->subDay()]);

    $this->get('/blog/monasteries')->assertOk()->assertSee('Prayer wheels.')->assertSee('BlogPosting', false);
});

it('301s trailing slashes and upper-case paths to the canonical url', function () {
    $this->get('/Trips/?destination=nepal')->assertRedirect('/trips?destination=nepal')->assertStatus(301);
});

it('follows managed redirects for migrated WordPress urls and slug renames', function () {
    $trip = siteTrip();
    Redirect::point('/trip/11n-12d-standardeverest-base-camp', '/trip/everest-base-camp-trek');

    $this->get('/trip/11n-12d-standardeverest-base-camp')->assertStatus(301)->assertRedirect(url('/trip/everest-base-camp-trek'));

    $trip->update(['slug' => 'ebc-trek']);
    $this->get('/trip/everest-base-camp-trek')->assertRedirect(url('/trip/ebc-trek'));
    $this->get('/trip/11n-12d-standardeverest-base-camp')->assertRedirect(url('/trip/ebc-trek'));
});

it('creates a lead from the trip enquiry (group departure)', function () {
    $trip = siteTrip();
    $dep = TripDeparture::create(['trip_id' => $trip->id, 'start_date' => now()->addMonths(2), 'seats_total' => 12, 'price' => 1500]);

    Livewire::test(TripEnquiry::class, ['trip' => $trip])
        ->call('selectDeparture', $dep->id)
        ->set('first_name', 'Ana')->set('last_name', 'Traveller')->set('email', 'ana@example.com')->set('adults', 2)->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('reference', fn ($r) => str_starts_with((string) $r, 'WH-'));

    $lead = Lead::first();
    expect($lead->type)->toBe(LeadType::Departure)
        ->and($lead->trip_departure_id)->toBe($dep->id)
        ->and((float) $lead->estimated_value)->toBe(3000.0);
});

it('requires privacy consent on enquiries', function () {
    Livewire::test(ContactForm::class)
        ->set('first_name', 'Bo')->set('last_name', 'Traveller')->set('email', 'bo@example.com')->set('message', 'Hello, a question about Mardi Himal.')
        ->call('submit')
        ->assertHasErrors(['privacy_accepted']);

    expect(Lead::count())->toBe(0);
});

it('walks the tailor-made builder and stores the brief', function () {
    Destination::create(['name' => 'Bhutan', 'slug' => 'bhutan']);

    Livewire::test(TailorMadeBuilder::class)
        ->call('next')->assertHasErrors(['destinations'])
        ->call('toggle', 'destinations', 'Bhutan')->call('next')->assertSet('step', 2)
        ->call('toggle', 'interests', 'culture')->call('next')->assertSet('step', 3)
        ->set('travel_month', now()->addMonths(3)->format('Y-m'))->call('next')->assertSet('step', 4)
        ->set('adults', 2)->set('budget', 'US$3,000 – 5,000')->call('next')->assertSet('step', 5)
        ->set('first_name', 'Kai')->set('last_name', 'Traveller')->set('email', 'kai@example.com')->set('privacy_accepted', true)
        ->call('submit')->assertHasNoErrors();

    $lead = Lead::first();
    expect($lead->type)->toBe(LeadType::TailorMade)
        ->and($lead->destinations)->toBe(['Bhutan'])
        ->and($lead->interests)->toBe(['Culture & heritage'])
        ->and($lead->budget)->toBe('US$3,000 – 5,000');
});

it('creates a company for partner enquiries', function () {
    Livewire::test(PartnerForm::class)
        ->set('company_name', 'Alpine Travel GmbH')->set('company_type', 'tour_operator')->set('country', 'Germany')
        ->set('first_name', 'Lena')->set('last_name', 'Traveller')->set('email', 'lena@alpine.test')->set('privacy_accepted', true)
        ->call('submit')->assertHasNoErrors();

    expect(Lead::first()->type)->toBe(LeadType::B2B)->and(Lead::first()->company->name)->toBe('Alpine Travel GmbH');
});

it('throttles repeated enquiries from one ip', function () {
    $submit = fn () => Livewire::test(ContactForm::class)
        ->set('first_name', 'Spam')->set('last_name', 'Traveller')->set('email', 'spam@example.com')->set('message', 'Buy cheap things now please')->set('privacy_accepted', true)
        ->call('submit');

    foreach (range(1, 5) as $i) {
        $submit()->assertHasNoErrors();
    }
    $submit()->assertHasErrors(['email']);
    expect(Lead::count())->toBe(5);
});

it('serves a sitemap containing trips', function () {
    siteTrip();
    $this->get('/sitemap.xml')->assertOk()->assertSee('/trip/everest-base-camp-trek', false);
});

it('rejects submissions that fill the honeypot', function () {
    config(['honeypot.enabled' => true]);

    $component = Livewire::test(ContactForm::class);
    $honeypot = $component->get('extraFields');
    $honeypot->myName = 'i am a bot';
    $honeypot->validFrom = encrypt(now()->subMinute()->timestamp);

    $component->set('extraFields', $honeypot)
        ->set('first_name', 'Bot')->set('last_name', 'Traveller')->set('email', 'bot@example.com')->set('message', 'Spammy message here ok')->set('privacy_accepted', true)
        ->call('submit')
        ->assertForbidden();

    expect(Lead::count())->toBe(0);
});

it('lists every nepal region in the destinations menu, including ones without journeys', function () {
    siteTrip();
    $nepal = Destination::where('slug', 'nepal')->first();
    Destination::create(['name' => 'Dolpo Region', 'slug' => 'dolpo-region', 'parent_id' => $nepal->id]);
    $tibet = Destination::create(['name' => 'Tibet', 'slug' => 'tibet']);
    siteTrip(['title' => 'Kailash Kora', 'slug' => 'kailash-kora'])->destinations()->sync([$tibet->id]);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Our home ground', 'Nepal', '1 journey · 2 regions', 'Dolpo', 'Made to order', 'Everest', '1 journey', 'Beyond Nepal', 'Tibet']);
});

it('requires a last name on enquiries and records the page the form was sent from', function () {
    $trip = siteTrip();

    Livewire::test(ContactForm::class)
        ->set('first_name', 'Ana')->set('email', 'ana@example.com')->set('message', 'A question about Everest.')->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['last_name' => 'required'])
        ->set('last_name', 'Silva')
        ->call('submit')
        ->assertHasNoErrors();

    $lead = Lead::latest('id')->first();
    expect($lead->contact->last_name)->toBe('Silva')
        ->and($lead->form_page)->not->toBeEmpty()
        ->and(UserAgent::summary('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1'))->toBe('Mobile · Safari · iOS');
});

it('groups top-level policy pages into one booking & policies section', function () {
    foreach (['risk-free-booking' => 'Risk free booking', 'terms-and-conditions' => 'Terms and Conditions'] as $path => $title) {
        Page::create(['title' => $title, 'slug' => $path, 'path' => $path, 'template' => 'legal', 'status' => PublishStatus::Published, 'published_at' => now()->subDay(),
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<h2>'.$title.'</h2><p>Policy text.</p>']]]]);
    }

    $this->get('/risk-free-booking')
        ->assertOk()
        ->assertSeeInOrder(['Booking &amp; policies', 'Risk free booking', 'Terms and Conditions'], false)
        ->assertSee('Questions before you book?')
        ->assertDontSee('<h2 id="risk-free-booking">', false);
});
