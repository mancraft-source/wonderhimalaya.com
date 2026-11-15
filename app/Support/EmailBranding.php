<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Brand details for the mail theme header and footer (resources/views/vendor/mail).
 * Reads the site settings, falling back to the published business details.
 */
final class EmailBranding
{
    public const LOGO_PATH = 'images/brand/logo-badge-160.png';

    /** @var array<string, string> network key => label, in footer order */
    public const SOCIAL_NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tripadvisor' => 'Tripadvisor',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'twitter' => 'X',
    ];

    /** @var array{name: string, legal_name: string, tagline: string, address: string, phone: string, email: string} */
    private const FALLBACK = [
        'name' => 'Wonder Himalaya',
        'legal_name' => 'Wonder Himalaya',
        'tagline' => 'Wander Always, Wonder Often',
        'address' => 'Thamel, Kathmandu, Nepal',
        'phone' => '+977-01-5928066',
        'email' => 'info@wonderhimalaya.com',
    ];

    public static function siteUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Absolute URL: mail clients cannot resolve relative image paths. */
    public static function logoUrl(): string
    {
        return self::siteUrl().'/'.self::LOGO_PATH;
    }

    /** @return array{name: string, legal_name: string, tagline: string, address: string, phone: string, email: string} */
    public static function company(): array
    {
        $settings = (array) Setting::get('company', []);

        $company = [];

        foreach (self::FALLBACK as $key => $fallback) {
            $value = trim((string) ($settings[$key] ?? ''));
            $company[$key] = $value !== '' ? $value : $fallback;
        }

        return $company;
    }

    /** @return array<string, string> label => url, only networks with a link set */
    public static function socialLinks(): array
    {
        $social = (array) Setting::get('social', []);
        $links = [];

        foreach (self::SOCIAL_NETWORKS as $key => $label) {
            $url = trim((string) ($social[$key] ?? ''));

            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                $links[$label] = $url;
            }
        }

        return $links;
    }

    /** Phone number usable in a tel: link. */
    public static function telHref(string $phone): string
    {
        return 'tel:'.preg_replace('/[^\d+]/', '', $phone);
    }
}
