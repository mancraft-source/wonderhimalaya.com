<?php

namespace App\Services\WordPress;

/**
 * Old WordPress path → new Laravel path.
 *
 * Holds exact mappings (trips, posts, pages, team, destinations) and the
 * pattern rules for archive/system URLs of the old site.
 */
class UrlMap
{
    /** WP Travel Engine / system pages and where their URLs should land. */
    public const SYSTEM_PAGES = [
        '/wp-travel-engine-checkout' => '/trips',
        '/wp-travel-engine-cart' => '/trips',
        '/checkout' => '/trips',
        '/travellers-information' => '/trips',
        '/confirmation' => '/trips',
        '/thank-you' => '/trips',
        '/trip-search-result' => '/trips',
        '/wishlist' => '/trips',
        '/my-account' => '/contact',
        '/enquiry-thank-you-page' => '/',
        '/custom-payment' => '/contact',
        '/privcy-policy' => '/privacy-policy',
        '/privcy-policy-2' => '/privacy-policy',
        '/join-group' => '/fixed-departures',
        '/home-2' => '/',
        '/trip' => '/trips',
        '/trip-types' => '/trips',
        '/trip-types/join-group-departure' => '/fixed-departures',
        '/trip-types/fix-departure' => '/fixed-departures',
        '/trip-packages-categories' => '/trips',
    ];

    /** @var array<string, string> */
    protected array $map = [];

    /** @var array<string, string> */
    protected array $types = [];

    public static function normalize(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        return '/'.trim(strtolower(rawurldecode($path)), '/');
    }

    public function add(string $old, string $new, string $type = 'other'): static
    {
        $this->map[self::normalize($old)] = $new;
        $this->types[self::normalize($old)] = $type;

        return $this;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->map;
    }

    public function type(string $old): ?string
    {
        return $this->types[self::normalize($old)] ?? null;
    }

    /** Resolve an old path; null when nothing is known about it. */
    public function resolve(string $old): ?string
    {
        $path = self::normalize($old);

        return $this->map[$path] ?? self::SYSTEM_PAGES[$path] ?? self::pattern($path);
    }

    /** Rules for archive URLs (taxonomies, pagination, authors). */
    public static function pattern(string $path): ?string
    {
        $path = self::normalize($path);
        $segments = array_values(array_filter(explode('/', $path)));
        $first = $segments[0] ?? '';
        $last = end($segments) ?: '';

        return match (true) {
            $first === 'trip-types' && $last === 'join-group-departure' => '/fixed-departures',
            $first === 'trip-types' && count($segments) === 2 => '/trips?tier='.$last,
            in_array($first, ['trip-difficulty', 'difficulty'], true) && count($segments) === 2 => '/trips?difficulty='.$last,
            in_array($first, ['trip-tag', 'trip_tag', 'trip-packages-categories'], true) => '/trips',
            $first === 'destinations' && count($segments) >= 2 => '/destination/'.$last,
            $first === 'category' && count($segments) >= 2 => $last === 'uncategorized' ? '/blog' : '/blog?category='.$last,
            $first === 'tag' => '/blog',
            $first === 'author' => '/about-us',
            $first === 'blog' && ($segments[1] ?? null) === 'page' => '/blog',
            default => null,
        };
    }
}
