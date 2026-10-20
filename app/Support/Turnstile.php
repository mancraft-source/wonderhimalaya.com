<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cloudflare Turnstile server-side verification. Shared by the web forms and the API.
 * When no secret is configured the check is disabled (always passes).
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.secret'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public static function verify(?string $token, ?string $ip = null): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if (blank($token) || strlen((string) $token) > 2048) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post(self::VERIFY_URL, array_filter([
                'secret' => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (Throwable $e) {
            Log::warning('Turnstile verification request failed', ['error' => $e->getMessage()]);

            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
