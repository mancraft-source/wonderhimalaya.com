<?php

namespace App\Support\Seo;

use App\Models\Setting;

/**
 * Site-wide SEO configuration, stored as the `seo` settings row and edited under SEO → SEO settings.
 * Every value has a default, so the site is fully SEO-ready before anyone opens the settings page.
 */
final class SeoSettings
{
    /** Longest title Google shows in full (~580px); the brand suffix is only added when the result stays within it. */
    public const MAX_TITLE_LENGTH = 60;

    /**
     * Content types with SEO templates. Keys match HasSeo::seoType().
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'trip' => 'Trips',
        'destination' => 'Destinations',
        'activity' => 'Activities (travel styles)',
        'page' => 'Pages',
        'post' => 'Blog posts',
        'team_member' => 'Team members',
    ];

    /**
     * Template variables offered in the admin, per type ("*" = every type).
     *
     * @var array<string, array<string, string>>
     */
    public const VARIABLES = [
        '*' => ['%%title%%' => 'Title or name', '%%excerpt%%' => 'Excerpt / intro (first 155 characters)', '%%sep%%' => 'Separator', '%%sitename%%' => 'Site name', '%%year%%' => 'Current year'],
        'trip' => ['%%duration%%' => 'Days, e.g. 14 Days', '%%price%%' => 'From price, e.g. US$1,585', '%%destination%%' => 'Main region', '%%difficulty%%' => 'Grade', '%%max_altitude%%' => 'Highest point, e.g. 5,364 m', '%%trip_code%%' => 'Trip code'],
        'destination' => ['%%parent%%' => 'Country (for regions)', '%%tagline%%' => 'Tagline'],
        'activity' => ['%%tagline%%' => 'Tagline'],
        'page' => ['%%parent%%' => 'Parent page'],
        'post' => ['%%category%%' => 'Category', '%%author%%' => 'Author', '%%date%%' => 'Publish date'],
        'team_member' => ['%%role%%' => 'Role'],
    ];

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'separator' => '|',
        'site_name' => 'Wonder Himalaya',
        'home_title' => 'Wonder Himalaya — Treks, Tours & Expeditions in Nepal, Tibet & Bhutan',
        'home_description' => 'Locally owned Kathmandu operator crafting guided treks, peak climbs and cultural journeys across Nepal, Tibet and Bhutan. Everest, Annapurna, Manaslu, Langtang and beyond.',
        'default_image' => null,
        'twitter_handle' => '@WonderH90546',
        'allow_indexing' => true,
        'robots_extra' => null,
        'organization_type' => 'TravelAgency',
        'verification' => ['google' => null, 'bing' => null, 'yandex' => null, 'pinterest' => null, 'facebook' => null],
        'types' => [
            'trip' => ['title' => '%%title%% %%sep%% %%sitename%%', 'description' => '%%excerpt%%', 'noindex' => false, 'sitemap' => true],
            'destination' => ['title' => '%%title%% treks & tours %%sep%% %%sitename%%', 'description' => '%%excerpt%%', 'noindex' => false, 'sitemap' => true],
            'activity' => ['title' => '%%title%% in Nepal & the Himalaya %%sep%% %%sitename%%', 'description' => '%%excerpt%%', 'noindex' => false, 'sitemap' => true],
            'page' => ['title' => '%%title%% %%sep%% %%sitename%%', 'description' => '%%excerpt%%', 'noindex' => false, 'sitemap' => true],
            'post' => ['title' => '%%title%% %%sep%% %%sitename%%', 'description' => '%%excerpt%%', 'noindex' => false, 'sitemap' => true],
            'team_member' => ['title' => '%%title%% — %%role%% %%sep%% %%sitename%%', 'description' => '%%excerpt%%', 'noindex' => false, 'sitemap' => true],
        ],
    ];

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return array_replace_recursive(self::DEFAULTS, (array) Setting::get('seo', []));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::all(), $key, $default);
    }

    /** @return array{title: string, description: string, noindex: bool, sitemap: bool} */
    public static function type(string $type): array
    {
        return self::all()['types'][$type] ?? self::DEFAULTS['types']['page'];
    }

    public static function separator(): string
    {
        return (string) (self::get('separator') ?: '|');
    }

    public static function siteName(): string
    {
        return (string) (self::get('site_name') ?: config('app.name'));
    }

    /** Search engines may index the site: production, and not switched off in the settings. */
    public static function indexable(): bool
    {
        return app()->isProduction() && (bool) self::get('allow_indexing', true);
    }

    public static function defaultImageUrl(): ?string
    {
        $path = self::get('default_image');

        return $path ? asset('storage/'.$path) : null;
    }
}
