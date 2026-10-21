<?php

namespace App\Support;

use App\Models\Trip;

/** Default trekking kit list (shown on trekking / climbing trips until per-trip lists are added in the CMS). */
final class PackingList
{
    /** @return array<string, array<int, string>> */
    public static function default(): array
    {
        return [
            'General' => ['Four-season sleeping bag (we can provide one on loan)', 'Down jacket (available on loan)', 'Daypack, 30–40 L, with rain cover', 'Duffel bag for the porter (provided)', 'Sleeping bag liner'],
            'Head & upper body' => ['Sun hat and warm beanie', 'Headlamp with spare batteries', 'UV-protection sunglasses', 'Buff or neck gaiter', 'Thermal base layers (top)', 'Fleece mid-layer', 'Waterproof, windproof shell jacket'],
            'Lower body & feet' => ['Trekking trousers (2 pairs)', 'Thermal base layers (bottom)', 'Waterproof over-trousers', 'Broken-in waterproof trekking boots', 'Camp shoes or sandals', 'Wool hiking socks (4–5 pairs)', 'Gaiters (for snow, in winter)'],
            'Hands' => ['Liner gloves', 'Insulated waterproof outer gloves'],
            'Health & first aid' => ['Personal medication', 'Sunscreen (SPF 50) and lip balm', 'Blister plasters', 'Water purification tablets', 'Hand sanitiser'],
            'Essentials & documents' => ['Passport and 2 passport photos', 'Copy of travel insurance (with evacuation cover)', 'Reusable water bottles (2 × 1 L)', 'Quick-dry towel and toiletries', 'Toilet paper and wet wipes', 'Snacks — energy bars, nuts', 'Trekking poles', 'Power bank and charging cables'],
        ];
    }

    /** Only show a kit list where it is relevant (treks, climbs, high routes). */
    public static function appliesTo(Trip $trip): bool
    {
        return $trip->activities->whereIn('slug', ['guided-trek', 'peak-climbing', 'camping', 'expedition'])->isNotEmpty()
            || (int) $trip->max_altitude_m >= 3000;
    }
}
