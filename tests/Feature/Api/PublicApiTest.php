<?php

use App\Enums\PublishStatus;
use App\Models\Activity;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use Tests\Support\Fixtures;

it('lists only published trips with a pagination envelope and cache headers', function () {
    Fixtures::trip(['title' => 'Annapurna Circuit']);
    Fixtures::trip(['title' => 'Draft trek', 'status' => PublishStatus::Draft]);
    Fixtures::trip(['title' => 'Future trek', 'published_at' => now()->addWeek()]);

    $response = $this->getJson('/api/v1/trips')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.title'))->toBe('Annapurna Circuit')
        ->and($response->json())->toHaveKeys(['data', 'links', 'meta'])
        ->and($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=300')
        ->and($response->headers->get('ETag'))->not->toBeNull();
});

it('filters trips by destination (including children), activity, duration, price, month and search', function () {
    $nepal = Fixtures::destination(['slug' => 'nepal', 'name' => 'Nepal']);
    $everest = Fixtures::destination(['slug' => 'everest', 'name' => 'Everest', 'parent_id' => $nepal->id]);
    $tibet = Fixtures::destination(['slug' => 'tibet', 'name' => 'Tibet']);

    $ebc = Fixtures::trip(['title' => 'Everest Base Camp', 'destination_id' => $everest->id, 'duration_days' => 14, 'price_from' => 1400]);
    $kailash = Fixtures::trip(['title' => 'Kailash Kora', 'destination_id' => $tibet->id, 'duration_days' => 16, 'price_from' => 3200]);
    $short = Fixtures::trip(['title' => 'Poon Hill', 'duration_days' => 5, 'price_from' => 600, 'sale_price' => 500]);
    $short->destinations()->attach($nepal);

    $trekking = Activity::create(['name' => 'Trekking', 'slug' => 'trekking']);
    $ebc->activities()->attach($trekking);

    Fixtures::departure($kailash, ['start_date' => now()->addMonths(3)->startOfMonth()->addDays(2)]);

    $titles = fn (string $query) => collect($this->getJson('/api/v1/trips?'.$query)->assertOk()->json('data'))->pluck('title')->sort()->values()->all();

    expect($titles('filter[destination]=nepal'))->toBe(['Everest Base Camp', 'Poon Hill'])
        ->and($titles('filter[destination]=everest'))->toBe(['Everest Base Camp'])
        ->and($titles('filter[activity]=trekking'))->toBe(['Everest Base Camp'])
        ->and($titles('filter[duration_min]=10&filter[duration_max]=15'))->toBe(['Everest Base Camp'])
        ->and($titles('filter[price_max]=550'))->toBe(['Poon Hill'])
        ->and($titles('filter[search]=kailash'))->toBe(['Kailash Kora'])
        ->and($titles('filter[month]='.now()->addMonths(3)->format('Y-m')))->toBe(['Kailash Kora']);

    $sorted = collect($this->getJson('/api/v1/trips?sort=-price')->json('data'))->pluck('title')->all();
    expect($sorted)->toBe(['Kailash Kora', 'Everest Base Camp', 'Poon Hill']);

    $this->getJson('/api/v1/trips?filter[unknown]=1')->assertStatus(400);
    expect($this->getJson('/api/v1/trips?per_page=500')->json('meta.per_page'))->toBe(50);
});

it('shows trip detail for published trips only', function () {
    $trip = Fixtures::trip(['slug' => 'everest-base-camp']);
    $trip->days()->create(['day_number' => 1, 'title' => 'Arrive in Kathmandu']);
    $trip->faqs()->create(['question' => 'Is it hard?', 'answer' => 'Moderate.']);
    Fixtures::departure($trip);
    Fixtures::trip(['slug' => 'secret', 'status' => PublishStatus::Draft]);

    $this->getJson('/api/v1/trips/everest-base-camp')
        ->assertOk()
        ->assertJsonPath('data.slug', 'everest-base-camp')
        ->assertJsonPath('data.days.0.title', 'Arrive in Kathmandu')
        ->assertJsonPath('data.faqs.0.question', 'Is it hard?')
        ->assertJsonCount(1, 'data.upcoming_departures')
        ->assertJsonStructure(['data' => ['seo' => ['title', 'canonical'], 'gallery', 'map', 'hero', 'prices']]);

    $this->getJson('/api/v1/trips/secret')->assertNotFound();
    $this->getJson('/api/v1/trips/everest-base-camp/departures')->assertOk()->assertJsonCount(1, 'data');
});

it('serves destinations tree, posts, pages with slashes, search and public settings', function () {
    $nepal = Fixtures::destination(['slug' => 'nepal']);
    Fixtures::destination(['slug' => 'everest', 'parent_id' => $nepal->id]);
    Fixtures::trip(['title' => 'Everest View', 'destination_id' => $nepal->id])->destinations()->attach($nepal);

    Post::create(['title' => 'Everest permits explained', 'slug' => 'everest-permits', 'status' => PublishStatus::Published, 'published_at' => now()->subDay()]);
    Post::create(['title' => 'Draft post', 'slug' => 'draft', 'status' => PublishStatus::Draft]);

    $parent = Page::create(['title' => 'Travel guide', 'slug' => 'travel-guide', 'status' => PublishStatus::Published]);
    Page::create(['title' => 'Nepal visa', 'slug' => 'nepal-visa', 'parent_id' => $parent->id, 'status' => PublishStatus::Published]);

    Setting::put('company', ['phone' => '+977 1 4000000', 'email' => 'info@example.test']);
    Setting::put('webhooks', [['url' => 'https://hooks.example.test', 'secret' => 'shh', 'events' => ['*']]]);

    $this->getJson('/api/v1/destinations')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'nepal')
        ->assertJsonPath('data.0.trips_count', 1)
        ->assertJsonPath('data.0.children.0.slug', 'everest');

    $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/posts/draft')->assertNotFound();
    $this->getJson('/api/v1/pages/travel-guide/nepal-visa')->assertOk()->assertJsonPath('data.title', 'Nepal visa');

    $search = $this->getJson('/api/v1/search?q=everest')->assertOk();
    expect(collect($search->json('data'))->pluck('type')->unique()->sort()->values()->all())->toBe(['post', 'trip']);

    $settings = $this->getJson('/api/v1/settings/public')->assertOk();
    expect($settings->json('data.company.phone'))->toBe('+977 1 4000000')
        ->and($settings->json('data'))->not->toHaveKey('webhooks');
});
