<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Serves captured live WordPress payloads (tests/Fixtures/wordpress) through
 * Http::fake() so the migration tests never touch the network.
 */
class FakesWordPress
{
    public static function fixture(string $name): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/wordpress/'.$name));
    }

    public static function json(string $name): array
    {
        return json_decode(self::fixture($name), true);
    }

    /** A real 4x4 PNG, so the media library can read the file. */
    public static function png(): string
    {
        $image = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    public static function boot(): void
    {
        $tmp = storage_path('framework/testing/wp-'.uniqid());

        config([
            'services.wordpress.url' => 'https://wonderhimalaya.com',
            'services.wordpress.cache' => false,
            'services.wordpress.delay_ms' => 0,
            'services.wordpress.media_cache_path' => $tmp.'/media-cache',
            'services.wordpress.media_map_path' => $tmp.'/media-map.json',
        ]);

        Storage::fake('public');
        Queue::fake();

        $list = fn (string $file) => Http::response(self::fixture($file), 200, ['X-WP-TotalPages' => '1', 'Content-Type' => 'application/json']);
        $json = fn (string $file) => Http::response(self::fixture($file), 200, ['Content-Type' => 'application/json']);

        Http::preventStrayRequests();
        Http::fake([
            'wonderhimalaya.com/wp-json/wptravelengine/v2/trips/6664/packages*' => $json('packages-6664.json'),
            'wonderhimalaya.com/wp-json/wptravelengine/v2/trips/2817/packages*' => $json('packages-2817.json'),
            'wonderhimalaya.com/wp-json/wptravelengine/v3/trips/6664/dates*' => $json('dates-6664.json'),
            'wonderhimalaya.com/wp-json/wptravelengine/v3/trips/*/dates*' => Http::response([], 200),
            'wonderhimalaya.com/wp-json/wptravelengine/v2/trips/6664*' => $json('trip-6664.json'),
            'wonderhimalaya.com/wp-json/wptravelengine/v2/trips/2817*' => $json('trip-2817.json'),
            'wonderhimalaya.com/wp-json/wp/v2/trip?*' => $list('trips.json'),
            'wonderhimalaya.com/wp-json/wp/v2/pages?*' => $list('pages.json'),
            'wonderhimalaya.com/wp-json/wp/v2/posts?*' => $list('posts.json'),
            'wonderhimalaya.com/wp-json/wp/v2/destination?*' => $list('destination.json'),
            'wonderhimalaya.com/wp-json/wp/v2/activities?*' => $list('activities.json'),
            'wonderhimalaya.com/wp-json/wp/v2/trip_types?*' => $list('trip_types.json'),
            'wonderhimalaya.com/wp-json/wp/v2/difficulty?*' => $list('difficulty.json'),
            'wonderhimalaya.com/wp-json/wp/v2/trip_tag?*' => $list('trip_tag.json'),
            'wonderhimalaya.com/wp-json/wp/v2/categories?*' => $list('categories.json'),
            'wonderhimalaya.com/wp-json/wp/v2/tags?*' => $list('tags.json'),
            'wonderhimalaya.com/wp-json/wp/v2/users?*' => $list('users.json'),
            'wonderhimalaya.com/wp-json/wp/v2/media?*' => Http::response('[]', 200, ['X-WP-TotalPages' => '1']),
            'wonderhimalaya.com/wp-json/wp/v2/media/*' => fn ($request) => Http::response([
                'id' => (int) basename(parse_url($request->url(), PHP_URL_PATH)),
                'source_url' => 'https://wonderhimalaya.com/wp-content/uploads/2025/02/media-'.basename(parse_url($request->url(), PHP_URL_PATH)).'.png',
                'alt_text' => 'Fixture image',
            ]),
            'wonderhimalaya.com/wp-content/uploads/*' => Http::response(self::png(), 200, ['Content-Type' => 'image/png']),
            'wonderhimalaya.com/' => Http::response(self::fixture('home.html'), 200, ['Content-Type' => 'text/html']),
            '*' => Http::response('Not found', 404),
        ]);
    }
}
