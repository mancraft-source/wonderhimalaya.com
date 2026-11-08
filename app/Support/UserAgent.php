<?php

namespace App\Support;

/** A readable "Device · Browser · OS" summary of a user-agent string, for staff (no lookup service needed). */
final class UserAgent
{
    public static function summary(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return '—';
        }

        $ua = strtolower($userAgent);

        $device = match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'Tablet',
            str_contains($ua, 'mobi') || str_contains($ua, 'iphone') || str_contains($ua, 'android') => 'Mobile',
            preg_match('/bot|crawl|spider|curl|python|http/', $ua) === 1 => 'Bot / script',
            default => 'Desktop',
        };

        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'samsungbrowser') => 'Samsung Internet',
            str_contains($ua, 'firefox') || str_contains($ua, 'fxios') => 'Firefox',
            str_contains($ua, 'chrome') || str_contains($ua, 'crios') => 'Chrome',
            str_contains($ua, 'safari') => 'Safari',
            default => null,
        };

        $os = match (true) {
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'mac os') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => null,
        };

        return implode(' · ', array_filter([$device, $browser, $os]));
    }
}
