<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * @tags Settings
 */
class SettingsController extends ApiController
{
    /**
     * Whitelisted public settings only (never secrets, webhooks, notification e-mails or bank details).
     */
    public const PUBLIC_KEYS = [
        'company.name', 'company.legal_name', 'company.tagline', 'company.email', 'company.phone', 'company.whatsapp',
        'company.address', 'company.city', 'company.country', 'company.registration', 'company.opening_hours',
        'company.map_url', 'company.latitude', 'company.longitude',
        'social', 'stats', 'reviews', 'memberships',
    ];

    public function __invoke(): JsonResponse
    {
        $data = [];

        foreach (self::PUBLIC_KEYS as $key) {
            $value = Setting::get($key);

            if ($value !== null) {
                data_set($data, $key, $value);
            }
        }

        $data['turnstile_site_key'] = config('services.turnstile.site_key');

        return response()->json(['data' => $data]);
    }
}
