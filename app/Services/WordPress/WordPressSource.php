<?php

namespace App\Services\WordPress;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Read model over the legacy WordPress content, plus the deterministic rules
 * that decide each item's new slug / path. Results are memoised per run.
 */
class WordPressSource
{
    public const TEAM_PARENT_ID = 4775;

    public const HOME_PAGE_ID = 453;

    public const FIX_DEPARTURE_SLUG = 'join-group-departure';

    /** WP Travel Engine/system pages that are not migrated as content. */
    public const EXCLUDED_PAGES = [
        'wp-travel-engine-checkout', 'wp-travel-engine-cart', 'my-account', 'travellers-information', 'checkout',
        'thank-you', 'enquiry-thank-you-page', 'confirmation', 'trip-search-result', 'wishlist', 'activities',
        'destination', 'trip-types', 'custom-payment', 'privcy-policy', 'privcy-policy-2', 'blog', 'home-2', 'join-group',
    ];

    public const GUIDE_PARENT = 'travel-guide';

    public const GUIDE_PAGES = [
        'nepal-visa', 'equipment-checklist', 'flight-delayed-or-cancellation', 'nepal-at-glance', 'best-trekking-seasons',
        'trip-grade', 'travel-insurance', 'recommended-medical-kit', 'recommended-flights', 'insurance',
    ];

    public const LEGAL_PAGES = ['terms-and-conditions', 'privacy-policy', 'risk-free-booking', 'how-to-book-a-trip-with-wonder-himalaya', 'alerts'];

    /** Homepage merchandising (old WP slugs). */
    public const FEATURED_TRIPS = ['10-days-annapurna-base-camp-shortest-trek', '16-days-annapurna-circuit-trek', '12-days-mardi-himal-trek', '14-days-everest-base-camp-trek'];

    public const TOP_SELLING_TRIPS = ['16-days-manaslu-circuit-trek'];

    public const POPULAR_TRIPS = ['14-days-everest-base-camp-trek', '16-days-manaslu-circuit-trek', '19-days-langtang-gosainkunda-circuit-trek', '25-days-tsum-valley-trek', '10-days-mount-kailash-tour'];

    /** @var array<string, mixed> */
    protected array $memo = [];

    public function __construct(public readonly WordPressClient $client) {}

    protected function remember(string $key, callable $callback): mixed
    {
        return $this->memo[$key] ??= $callback();
    }

    // ---------------------------------------------------------------- raw data

    /** @return array<int, array<string, mixed>> keyed by WP id */
    public function trips(): array
    {
        return $this->remember('trips', fn () => collect($this->client->all('wp/v2/trip'))
            ->reject(fn ($t) => self::isTestTrip($t))
            ->keyBy('id')->all());
    }

    public static function isTestTrip(array $trip): bool
    {
        return ($trip['slug'] ?? '') === 'test-trip-do-not-buy'
            || (bool) preg_match('/\btest trip\b/i', TitleCleaner::text($trip['title']['rendered'] ?? ''));
    }

    /** @return array<string, mixed> */
    public function tripDetail(int $id): array
    {
        // Not memoised: these payloads are large and only needed once per trip.
        return (array) $this->client->get("wptravelengine/v2/trips/{$id}");
    }

    /** @return array<int, array<string, mixed>> */
    public function tripPackages(int $id): array
    {
        try {
            return (array) $this->client->get("wptravelengine/v2/trips/{$id}/packages");
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function tripDates(int $id): array
    {
        try {
            return (array) $this->client->get("wptravelengine/v3/trips/{$id}/dates");
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> keyed by WP id */
    public function pages(): array
    {
        return $this->remember('pages', fn () => collect($this->client->all('wp/v2/pages'))->keyBy('id')->all());
    }

    /** @return array<int, array<string, mixed>> keyed by WP id */
    public function posts(): array
    {
        return $this->remember('posts', fn () => collect($this->client->all('wp/v2/posts'))->keyBy('id')->all());
    }

    /** @return array<int, array<string, mixed>> keyed by WP id */
    public function terms(string $taxonomy): array
    {
        return $this->remember("terms.$taxonomy", fn () => collect($this->client->all("wp/v2/{$taxonomy}"))->keyBy('id')->all());
    }

    /** @return array<int, array<string, mixed>> keyed by WP id */
    public function users(): array
    {
        return $this->remember('users', function () {
            try {
                return collect($this->client->all('wp/v2/users'))->keyBy('id')->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** Full media library index keyed by id (only fetched when media is imported). */
    public function media(): array
    {
        return $this->remember('media', function () {
            try {
                return collect($this->client->all('wp/v2/media'))->keyBy('id')->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** @return array<string, int> source_url => id */
    public function mediaByUrl(): array
    {
        return $this->remember('media.urls', function () {
            $urls = [];

            foreach ($this->media() as $id => $item) {
                if (! empty($item['source_url'])) {
                    $urls[$item['source_url']] = $id;
                }
            }

            return $urls;
        });
    }

    /** @return array{url: string, alt: string, id: int}|null */
    public function mediaItem(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $item = $this->media()[$id] ?? $this->remember("media.$id", function () use ($id) {
            try {
                return (array) $this->client->get("wp/v2/media/{$id}");
            } catch (\Throwable) {
                return [];
            }
        });

        if (empty($item['source_url'])) {
            return null;
        }

        return [
            'id' => $id,
            'url' => $item['source_url'],
            'alt' => TitleCleaner::text($item['alt_text'] ?? '') ?: TitleCleaner::text($item['title']['rendered'] ?? ''),
        ];
    }

    public function homeHtml(): string
    {
        return $this->remember('home.html', fn () => $this->client->html('/'));
    }

    // ----------------------------------------------------------------- rules

    /** @return array<int, string> term ids → slugs for a trip taxonomy field */
    public function termSlugs(array $item, string $taxonomy, ?string $field = null): array
    {
        $terms = $this->terms($taxonomy);

        return collect($item[$field ?? $taxonomy] ?? [])
            ->map(fn ($id) => $terms[$id]['slug'] ?? null)
            ->filter()->values()->all();
    }

    public function isFixedDeparture(array $trip): bool
    {
        return in_array(self::FIX_DEPARTURE_SLUG, $this->termSlugs($trip, 'trip_types'), true)
            || TitleCleaner::isFixedDeparture($trip['title']['rendered'] ?? '');
    }

    public function tripTitle(array $trip): string
    {
        $isTrek = array_intersect($this->termSlugs($trip, 'activities'), ['guided-trek']) !== [];

        return TitleCleaner::title($trip['title']['rendered'] ?? '', $isTrek);
    }

    /** @return array<int, string> WP id → new slug */
    public function tripSlugs(): array
    {
        return $this->remember('trip.slugs', fn () => TitleCleaner::uniqueSlugs(collect($this->trips())->map(fn ($t) => [
            'id' => $t['id'],
            'title' => $this->tripTitle($t),
            'date' => $t['date_gmt'] ?? $t['date'] ?? '',
            'fixed' => $this->isFixedDeparture($t),
            'days' => Arr::get($t, 'duration.days'),
        ])->values()->all()));
    }

    /**
     * Keep WP slugs but drop duplicate-suffixes ("-2", "-3") when the clean
     * version is free. Oldest posts claim first.
     *
     * @return array<int, string>
     */
    public function postSlugs(): array
    {
        return $this->remember('post.slugs', function () {
            $posts = collect($this->posts())->sortBy(fn ($p) => [$p['date_gmt'] ?? $p['date'], $p['id']]);
            $original = $posts->pluck('slug')->flip()->all();
            $taken = [];
            $result = [];

            foreach ($posts as $post) {
                $slug = $post['slug'];
                $stripped = preg_replace('/-\d$/', '', $slug);

                if ($stripped !== $slug && ! isset($original[$stripped]) && ! isset($taken[$stripped])) {
                    $slug = $stripped;
                }

                for ($n = 2; isset($taken[$slug]); $n++) {
                    $slug = $post['slug'].'-'.$n;
                }

                $taken[$slug] = true;
                $result[$post['id']] = $slug;
            }

            return $result;
        });
    }

    public function isTeamPage(array $page): bool
    {
        return (int) ($page['parent'] ?? 0) === self::TEAM_PARENT_ID;
    }

    /** New path for a WP page, or null when it is not migrated as a Page. */
    public function pagePath(array $page): ?string
    {
        $slug = $page['slug'];

        if ((int) $page['id'] === self::HOME_PAGE_ID || in_array($slug, self::EXCLUDED_PAGES, true) || $this->isTeamPage($page)) {
            return null;
        }

        if (in_array($slug, self::GUIDE_PAGES, true)) {
            return self::GUIDE_PARENT.'/'.$slug;
        }

        return $slug;
    }

    public function pageTemplate(array $page): string
    {
        return match (true) {
            in_array($page['slug'], self::GUIDE_PAGES, true) => 'guide',
            in_array($page['slug'], self::LEGAL_PAGES, true) => 'legal',
            default => 'default',
        };
    }

    public static function teamSlug(string $wpSlug): string
    {
        return preg_replace('/-wonder-himalaya(-guide)?$/', '', $wpSlug) ?: $wpSlug;
    }

    public function oldPath(array $item): string
    {
        return UrlMap::normalize((string) ($item['link'] ?? '/'));
    }

    /** Every old URL we know about, mapped to its new path. */
    public function urlMap(): UrlMap
    {
        return $this->remember('urlmap', function () {
            $map = new UrlMap;

            foreach ($this->trips() as $id => $trip) {
                $map->add('/trip/'.$trip['slug'], '/trip/'.$this->tripSlugs()[$id], 'trip');
            }

            foreach ($this->posts() as $id => $post) {
                $map->add($this->oldPath($post), '/blog/'.$this->postSlugs()[$id], 'post');
            }

            foreach ($this->pages() as $page) {
                if ($this->isTeamPage($page)) {
                    $map->add($this->oldPath($page), '/our-team/'.self::teamSlug($page['slug']), 'team');
                } elseif (($path = $this->pagePath($page)) !== null) {
                    $map->add($this->oldPath($page), '/'.$path, 'page');
                }
            }

            foreach ($this->terms('destination') as $term) {
                $map->add($this->oldPath($term), '/destination/'.$term['slug'], 'destination');
            }

            foreach ($this->terms('activities') as $term) {
                $map->add($this->oldPath($term), '/activities/'.$term['slug'], 'activity');
            }

            foreach ($this->terms('categories') as $term) {
                $map->add($this->oldPath($term), $term['slug'] === 'uncategorized' ? '/blog' : '/blog?category='.$term['slug'], 'category');
            }

            return $map;
        });
    }

    public static function stripSiteName(?string $title): ?string
    {
        if ($title === null) {
            return null;
        }

        $title = TitleCleaner::text($title);

        return trim((string) preg_replace('/\s*[\-–|]\s*Wonder Himalaya\s*$/iu', '', $title)) ?: null;
    }

    /**
     * SEO overrides from Yoast. The title is only kept when it was customised
     * (the default "{title} - Wonder Himalaya" pattern is left to the new site).
     *
     * @return array<string, mixed>
     */
    public static function seo(array $item, ?string $wpTitle = null): array
    {
        $yoast = $item['yoast_head_json'] ?? [];
        $title = self::stripSiteName($yoast['title'] ?? null);
        $wpTitle ??= TitleCleaner::text($item['title']['rendered'] ?? $item['name'] ?? '');

        if ($title !== null && Str::lower($title) === Str::lower(TitleCleaner::text($wpTitle))) {
            $title = null;
        }

        $seo = [
            'title' => $title,
            'description' => isset($yoast['description']) ? TitleCleaner::text($yoast['description']) : null,
            'canonical' => null,
        ];

        if (($yoast['robots']['index'] ?? 'index') === 'noindex') {
            $seo['noindex'] = true;
        }

        return $seo;
    }
}
