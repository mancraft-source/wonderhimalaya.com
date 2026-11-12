<?php

use App\Enums\PublishStatus;
use App\Filament\Pages\ManageSeoSettings;
use App\Filament\Pages\SeoOverview;
use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\User;
use App\Support\Seo\SeoAnalyzer;
use App\Support\Seo\SeoSettings;
use App\Support\Seo\SeoTemplate;
use Database\Seeders\RolesAndPermissionsSeeder;

function seoTrip(array $attrs = []): Trip
{
    return Trip::create($attrs + [
        'title' => 'Everest Base Camp Trek',
        'slug' => 'everest-base-camp-trek',
        'status' => PublishStatus::Published,
        'published_at' => now()->subDay(),
        'duration_days' => 14,
        'price_from' => 1585,
        'excerpt' => 'The classic trek to the foot of the world.',
    ]);
}

function seoPage(string $slug, array $seo = []): Page
{
    return Page::create(['title' => str($slug)->headline()->toString(), 'slug' => $slug, 'path' => $slug, 'status' => PublishStatus::Published, 'published_at' => now()->subDay(), 'blocks' => [], 'seo' => $seo]);
}

it('renders title templates with variables and tidies empty ones', function () {
    expect(SeoTemplate::render('%%title%% — %%role%% %%sep%% %%sitename%%', ['title' => 'Ram']))->toBe('Ram | Wonder Himalaya')
        ->and(SeoTemplate::render('%%title%% %%sep%% %%duration%% %%sep%% %%sitename%%', ['title' => 'EBC', 'duration' => '14 Days']))->toBe('EBC | 14 Days | Wonder Himalaya');
});

it('uses the content type template unless the record has its own title', function () {
    Setting::put('seo', ['types' => ['trip' => ['title' => '%%title%% (%%duration%%) %%sep%% %%sitename%%', 'description' => 'From %%price%%. %%excerpt%%']]]);
    $trip = seoTrip();

    $this->get('/trip/everest-base-camp-trek')
        ->assertSee('<title>Everest Base Camp Trek (14 Days) | Wonder Himalaya</title>', false)
        ->assertSee('content="From US$1,585. The classic trek to the foot of the world."', false);

    $trip->update(['seo' => ['title' => 'EBC in two weeks']]);

    $this->get('/trip/everest-base-camp-trek')->assertSee('<title>EBC in two weeks | Wonder Himalaya</title>', false);
});

it('outputs robots, social overrides and verification codes in the head', function () {
    Setting::put('seo', ['verification' => ['google' => 'g-token-123']]);
    seoTrip(['seo' => ['noindex' => true, 'og_title' => 'Share me', 'og_image' => 'seo/share.jpg']]);

    $this->get('/trip/everest-base-camp-trek')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<meta name="google-site-verification" content="g-token-123">', false)
        ->assertSee('<meta property="og:title" content="Share me">', false)
        ->assertSee('storage/seo/share.jpg', false);

    app()->detectEnvironment(fn () => 'production');
    $this->get('/trip/everest-base-camp-trek')->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('canonicalises and noindexes filtered journey listings', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/trips')->assertSee('index, follow', false);
    $this->get('/trips?destination=nepal')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="'.route('trips.index').'">', false);
});

it('leaves noindexed, excluded and switched-off content out of the sitemap', function () {
    seoTrip();
    seoTrip(['title' => 'Hidden Trek', 'slug' => 'hidden-trek', 'seo' => ['noindex' => true]]);
    seoTrip(['title' => 'Unlisted Trek', 'slug' => 'unlisted-trek', 'seo' => ['exclude_from_sitemap' => true]]);
    seoPage('privacy-policy');

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($xml)->toContain('/trip/everest-base-camp-trek')->toContain('/privacy-policy')
        ->not->toContain('hidden-trek')->not->toContain('unlisted-trek');

    Setting::put('seo', ['types' => ['page' => ['sitemap' => false]]]);

    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/privacy-policy');
});

it('adds custom robots.txt rules in production and blocks everything when indexing is off', function () {
    app()->detectEnvironment(fn () => 'production');
    Setting::put('seo', ['robots_extra' => "User-agent: GPTBot\nDisallow: /"]);

    $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('User-agent: GPTBot')->assertSee('Sitemap: ');

    Setting::put('seo', ['allow_indexing' => false]);

    expect($this->get('/robots.txt')->getContent())->toStartWith("User-agent: *\nDisallow: /\n");
});

it('logs real 404s for the monitor but not redirected urls or probes', function () {
    Redirect::point('/old-trek', '/trips');

    $this->get('/missing-trek')->assertNotFound();
    $this->get('/missing-trek')->assertNotFound();
    $this->get('/old-trek')->assertRedirect();
    $this->get('/wp-login.php')->assertNotFound();
    $this->get('/build/missing.js')->assertNotFound();

    expect(NotFoundLog::pluck('hits', 'path')->all())->toBe(['/missing-trek' => 2]);
});

it('imports a pasted list of redirects', function () {
    $result = ListRedirects::importLines("/a /trip/x\n/b,/trips,302\n\n/gone 410\njust-one-path");

    expect($result)->toBe(['imported' => 3, 'skipped' => 1])
        ->and(Redirect::pluck('status_code', 'from_path')->all())->toEqualCanonicalizing(['/a' => 301, '/b' => 302, '/gone' => 410]);
});

it('scores pages against the focus keyphrase', function () {
    $html = '<p>The Everest base camp trek climbs to 5,364 m.</p>'.str_repeat('<p>Long days on the trail with views of the highest mountains on earth.</p>', 30).'<a href="/trips">More</a>';

    $good = SeoAnalyzer::analyze(['title' => 'Everest Base Camp Trek — 14 days in the Khumbu', 'description' => str_repeat('Everest base camp trek ', 6), 'slug' => 'everest-base-camp-trek', 'keyphrase' => 'Everest base camp trek', 'html' => $html]);
    $bad = SeoAnalyzer::analyze(['title' => 'Trek', 'description' => '', 'slug' => 'x', 'keyphrase' => 'annapurna circuit', 'html' => '<p>Short.</p><img src="/a.jpg">']);

    expect($good['score'])->toBeGreaterThan(80)
        ->and($bad['score'])->toBeLessThan(40)
        ->and(collect($bad['checks'])->pluck('label')->implode(' '))->toContain('no alt text')->toContain('Add the focus keyphrase to the SEO title');
});

it('stores a score when content is saved', function () {
    expect(seoTrip()->seoScore())->toBeInt()->toBeGreaterThan(0);
});

it('shows the SEO admin screens to content managers', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->admin()->create();
    seoTrip();
    NotFoundLog::create(['path' => '/missing', 'hits' => 3, 'last_hit_at' => now()]);

    $this->actingAs($admin)->get(SeoOverview::getUrl())->assertOk()->assertSee('Needs attention')->assertSee('/missing');
    $this->actingAs($admin)->get(ManageSeoSettings::getUrl())->assertOk()->assertSee('SEO title template');
    $this->actingAs($admin)->get(NotFoundLogResource::getUrl())->assertOk()->assertSee('/missing');
});

it('keeps defaults when nothing is configured', function () {
    expect(SeoSettings::type('trip')['title'])->toBe('%%title%% %%sep%% %%sitename%%')
        ->and(SeoSettings::separator())->toBe('|');
});

it('suggests matching journeys on the 404 page and keeps it out of the index', function () {
    seoTrip();
    seoTrip(['title' => 'Mardi Himal Trek', 'slug' => 'mardi-himal-trek']);

    $this->get('/old-everest-base-camp-package')
        ->assertNotFound()
        ->assertSee('Were you looking for')
        ->assertSee('Everest Base Camp Trek')
        ->assertDontSee('Mardi Himal Trek')
        ->assertSee('value="everest base camp package"', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    $this->get('/qqq-zzz')->assertNotFound()->assertSee('Popular right now');
});

it('imports suggested titles, descriptions and keyphrases from the audit csv', function () {
    $trip = seoTrip(['seo' => ['title' => 'Old title', 'noindex' => false]]);
    $page = seoPage('about-us', ['description' => 'Keep me']);
    $header = ['Type', 'URL', 'Focus keyphrase', 'Suggested meta description', 'Paste into admin: SEO title', 'Where to edit'];
    $rows = [
        ['Trip', url('/trip/everest-base-camp-trek'), 'everest base camp trek', 'Everest Base Camp trek in 14 days from US$1,585.', 'Everest Base Camp Trek – 14 Days', "https://wonderhimalaya.com/admin/trips/{$trip->id}/edit"],
        ['Page', url('/about-us'), 'about wonder himalaya', 'About the team.', 'About Wonder Himalaya', "https://wonderhimalaya.com/admin/pages/{$page->id}/edit"],
        ['Listing page', 'https://wonderhimalaya.com/', 'nepal trekking company', 'Locally owned operator in Thamel.', 'Wonder Himalaya – Trekking in Nepal', 'SEO → SEO settings (homepage)'],
    ];
    $file = 'storage/framework/testing/seo-import.csv';
    @mkdir(base_path('storage/framework/testing'), 0777, true);
    $handle = fopen(base_path($file), 'w');
    foreach ([$header, ...$rows] as $row) {
        fputcsv($handle, $row, escape: '\\');
    }
    fclose($handle);

    $this->artisan('seo:import', ['file' => $file, '--only-blank' => true])->assertSuccessful();
    expect($trip->fresh()->seo['title'])->toBe('Old title')
        ->and(SeoSettings::get('home_title'))->toBe('Wonder Himalaya – Trekking in Nepal');

    $this->artisan('seo:import', ['file' => $file])->assertSuccessful();
    expect($trip->fresh()->seo)->toMatchArray(['title' => 'Everest Base Camp Trek – 14 Days', 'focus_keyphrase' => 'everest base camp trek', 'noindex' => false])
        ->and($page->fresh()->seo['description'])->toBe('About the team.');

    unlink(base_path($file));
});
