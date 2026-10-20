<?php

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Difficulty;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\Tier;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\TripFaq;
use App\Models\TripPrice;
use App\Services\WordPress\Importers\ReviewImporter;
use Tests\Support\FakesWordPress;

beforeEach(fn () => FakesWordPress::boot());

it('imports taxonomies', function () {
    $this->artisan('wp:import-taxonomies', ['--skip-media' => true])->assertSuccessful();

    $everest = Destination::where('slug', 'everest-region')->first();

    expect(Destination::count())->toBe(2)
        ->and($everest->parent->slug)->toBe('nepal')
        ->and($everest->wp_id)->toBe(43)
        ->and(Activity::where('slug', 'guided-trek')->exists())->toBeTrue()
        ->and(Difficulty::pluck('level', 'slug')->all())->toMatchArray(['easy' => 1, 'medium' => 2, 'hard' => 3, 'extreme' => 4])
        ->and(Tier::orderBy('sort')->pluck('slug')->all())->toBe(['standard', 'premium', 'luxury']);
});

it('imports a trip with all mapped fields', function () {
    $this->artisan('wp:import-taxonomies', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:import-trips', ['--skip-media' => true])->assertSuccessful();

    $trip = Trip::where('wp_id', 6664)->firstOrFail();

    expect($trip->title)->toBe('Everest Base Camp Trek')
        ->and($trip->slug)->toBe('everest-base-camp-trek-fixed-departure')
        ->and($trip->wp_slug)->toBe('11n-12d-standardeverest-base-camp')
        ->and($trip->subtitle)->toBe('12 days · Fixed departure')
        ->and($trip->trip_code)->toBe('WHEBCH-0112')
        ->and($trip->is_fixed_departure)->toBeTrue()
        ->and($trip->duration_days)->toBe(12)
        ->and($trip->duration_nights)->toBe(11)
        ->and($trip->group_min)->toBe(2)
        ->and($trip->group_max)->toBe(10)
        ->and($trip->min_age)->toBeNull()
        ->and($trip->max_altitude_m)->toBe(5364)
        ->and($trip->accommodation)->toBe('Tea House')
        ->and($trip->best_season)->toBe('Autumn')
        ->and($trip->facts)->toHaveKey('Accommodation', 'Tea House')
        ->and($trip->highlights[0])->toStartWith("Nepal's top trekking destination")
        ->and($trip->includes)->toContain('All Required Permits')
        ->and($trip->excludes)->toContain('Travel Insurance')
        ->and((float) $trip->price_from)->toBe(1400.0)
        ->and($trip->overview)->not->toContain('wp:paragraph')
        ->and($trip->excerpt)->not->toBeEmpty()
        ->and($trip->seo['description'])->toStartWith('Join Wonder Himalaya')
        ->and($trip->seo['title'])->toBeNull()
        ->and($trip->difficulty->slug)->toBe('hard')
        ->and($trip->destination->slug)->toBe('everest-region')
        ->and($trip->destinations()->count())->toBe(1)
        ->and($trip->tiers()->pluck('slug')->all())->toBe(['standard'])
        ->and($trip->status->value)->toBe('published')
        ->and($trip->published_at->toDateString())->toBe('2026-01-27');

    $day1 = $trip->days()->first();
    expect($trip->days()->count())->toBe(12)
        ->and($day1->day_number)->toBe(1)
        ->and($day1->altitude_m)->toBe(2860)
        ->and((float) $day1->distance_km)->toBe(8.0)
        ->and($day1->duration)->toBe('3-4 hrs')
        ->and($day1->accommodation)->toBe('Tea House')
        ->and($trip->faqs()->count())->toBe(9)
        ->and($trip->prices()->first()->label)->toBe('Group')
        ->and((float) $trip->prices()->first()->price)->toBe(1400.0)
        // 30 consecutive daily dates = rolling availability, not fixed departures.
        ->and($trip->departures()->count())->toBe(0);

    $classic = Trip::where('wp_id', 2817)->first();
    expect($classic->slug)->toBe('everest-base-camp-trek')
        ->and($classic->is_featured)->toBeTrue()
        ->and($classic->is_popular)->toBeTrue()
        ->and($classic->prices()->count())->toBe(3)
        ->and($classic->related()->pluck('trips.id')->all())->toBe([$trip->id]);
});

it('is idempotent', function () {
    $counts = fn () => [Trip::count(), TripDay::count(), TripFaq::count(), TripPrice::count(), Page::count(), Post::count(), TeamMember::count(), Testimonial::count(), Redirect::count()];
    $snapshots = [];

    foreach (range(1, 2) as $run) {
        $this->artisan('wp:import-taxonomies', ['--skip-media' => true])->assertSuccessful();
        $this->artisan('wp:import-trips', ['--skip-media' => true])->assertSuccessful();
        $this->artisan('wp:import-pages', ['--skip-media' => true])->assertSuccessful();
        $this->artisan('wp:import-team', ['--skip-media' => true])->assertSuccessful();
        $this->artisan('wp:import-posts', ['--skip-media' => true])->assertSuccessful();
        $this->artisan('wp:import-reviews')->assertSuccessful();
        $this->artisan('wp:build-redirects')->assertSuccessful();
        $snapshots[] = $counts();
    }

    expect($snapshots[1])->toBe($snapshots[0])
        ->and(Trip::count())->toBe(2)
        ->and(TripDay::count())->toBe(12 + 14)
        ->and(TripFaq::count())->toBe(9 + 12) // one FAQ is duplicated on WP #2817
        ->and(TripPrice::count())->toBe(4)
        ->and(Destination::count())->toBe(2)
        ->and(Page::count())->toBe(3)
        ->and(Post::count())->toBe(2)
        ->and(TeamMember::count())->toBe(1)
        ->and(Testimonial::count())->toBe(2);
});

it('does not write anything on a dry run', function () {
    $this->artisan('wp:import-taxonomies', ['--dry-run' => true])->assertSuccessful();

    expect(Destination::count())->toBe(0);
});

it('imports pages with guide paths, templates and blocks, skipping system pages', function () {
    $this->artisan('wp:import-pages', ['--skip-media' => true])->assertSuccessful();

    $visa = Page::where('wp_id', 5930)->firstOrFail();

    expect($visa->path)->toBe('travel-guide/nepal-visa')
        ->and($visa->parent->path)->toBe('travel-guide')
        ->and($visa->template)->toBe('guide')
        ->and($visa->blocks[0]['type'])->toBe('rich_text')
        ->and($visa->blocks[0]['data']['content'])->toContain('<h2>')->not->toContain('elementor')
        ->and(Page::where('slug', 'wishlist')->exists())->toBeFalse()
        ->and(Page::where('slug', 'home-2')->exists())->toBeFalse()
        ->and(Page::where('wp_id', 4851)->exists())->toBeFalse(); // team member, not a page
});

it('imports team members from Elementor profile pages', function () {
    $this->artisan('wp:import-team', ['--skip-media' => true])->assertSuccessful();

    $member = TeamMember::firstOrFail();

    expect($member->name)->toBe('Niranjan Nepal')
        ->and($member->slug)->toBe('niranjan-nepal')
        ->and($member->role)->toBe('Co- Founder and Managing Director')
        ->and($member->experience_years)->toBe(6)
        ->and($member->bio)->toContain('co-founder and managing director')
        ->and($member->social)->toHaveKey('instagram')
        ->and($member->is_guide)->toBeFalse();
});

it('imports posts keeping slugs and stripping duplicate suffixes', function () {
    $this->artisan('wp:import-taxonomies', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:import-posts', ['--skip-media' => true])->assertSuccessful();

    $post = Post::where('wp_id', 6888)->firstOrFail();

    expect($post->slug)->toBe('can-beginners-trek-in-nepal-your-friendly-guide-for-2025')
        ->and($post->wp_slug)->toBe('can-beginners-trek-in-nepal-your-friendly-guide-for-2025-2')
        ->and(Post::where('wp_id', 7334)->value('slug'))->toBe('dordi-himal-a-potential-alternative-of-annapurna-circuit-trek')
        ->and(Post::where('wp_id', 7334)->first()->body)->toContain('<figure>')
        ->and($post->category?->slug)->toBe('expert-guides-travel-advice');
});

it('builds redirects for old URLs', function () {
    $this->artisan('wp:import-taxonomies', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:import-trips', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:import-pages', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:import-posts', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:build-redirects')->assertSuccessful();

    $to = fn (string $from) => Redirect::where('from_path', $from)->value('to_path');

    expect($to('/trip/11n-12d-standardeverest-base-camp'))->toBe('/trip/everest-base-camp-trek-fixed-departure')
        ->and($to('/trip/14-days-everest-base-camp-trek'))->toBe('/trip/everest-base-camp-trek')
        ->and($to('/nepal-visa'))->toBe('/travel-guide/nepal-visa')
        ->and($to('/can-beginners-trek-in-nepal-your-friendly-guide-for-2025-2'))->toBe('/blog/can-beginners-trek-in-nepal-your-friendly-guide-for-2025')
        ->and($to('/trip-types/premium'))->toBe('/trips?tier=premium')
        ->and($to('/trip-types/join-group-departure'))->toBe('/fixed-departures')
        ->and($to('/trip-difficulty/hard'))->toBe('/trips?difficulty=hard')
        ->and($to('/destinations/nepal/everest-region'))->toBe('/destination/everest-region')
        ->and($to('/join-group'))->toBe('/fixed-departures')
        ->and($to('/privcy-policy-2'))->toBe('/privacy-policy')
        ->and($to('/author/admin'))->toBe('/about-us')
        ->and(Redirect::where('from_path', '/activities/guided-trek')->exists())->toBeFalse();
});

it('downloads media once and records the original url', function () {
    $this->artisan('wp:import-taxonomies', ['--skip-media' => true])->assertSuccessful();
    $this->artisan('wp:import-trips', ['--id' => [6664]])->assertSuccessful();
    $this->artisan('wp:import-trips', ['--id' => [6664]])->assertSuccessful();

    $trip = Trip::where('wp_id', 6664)->first();
    $hero = $trip->getFirstMedia('hero');

    expect($hero)->not->toBeNull()
        ->and($hero->getCustomProperty('wp_url'))->toBe('https://wonderhimalaya.com/wp-content/uploads/2025/02/media-4600.png')
        ->and($trip->getMedia('gallery'))->toHaveCount(1)
        ->and($trip->getMedia('gallery')->first()->getCustomProperty('alt'))->toBe('everest-view-wonder-himalaya0nepal')
        ->and($trip->getMedia('map'))->toHaveCount(1)
        ->and($trip->media()->count())->toBe(3);
});

it('parses Trustindex reviews', function () {
    $reviews = ReviewImporter::parse(FakesWordPress::fixture('home.html'));

    expect($reviews)->toHaveCount(2)
        ->and($reviews[0]['author_name'])->toBe('Fabio Izzo')
        ->and($reviews[0]['rating'])->toBe(5)
        ->and($reviews[0]['source'])->toBe('google')
        ->and($reviews[0]['body'])->toStartWith('I booked my 10-day manaslu')
        ->and($reviews[0]['reviewed_at'])->toBe('2026-05-06');
});
