<?php

namespace App\Support;

/**
 * Groups flat include/exclude lines (as imported from WordPress) into
 * reader-friendly categories, mirroring how the reference itinerary pages present them.
 */
final class TripInclusions
{
    /** @var array<string, array{icon: string, pattern: string}> Checked in order; first match wins. */
    private const CATEGORIES = [
        'Transport & flights' => ['icon' => 'paper-airplane', 'pattern' => '/flight|airport|air ?fare|transfer|transport|vehicle|\bbus\b|jeep|drive|helicopter|\bheli\b/i'],
        'Permits & fees' => ['icon' => 'identification', 'pattern' => '/permit|tims|\bfees?\b|national park|conservation|entry|municipality|tax/i'],
        'Guide & staff' => ['icon' => 'user-group', 'pattern' => '/guide|porter|staff|sherpa|crew|assistant|leader/i'],
        'Accommodation' => ['icon' => 'home-modern', 'pattern' => '/hotel|lodge|tea ?house|accommodation|guest ?house|camping|\btents?\b|room|night/i'],
        'Meals & drinks' => ['icon' => 'cake', 'pattern' => '/meal|breakfast|lunch|dinner|food|water|\btea\b|coffee|fruit|beverage|drink|alcohol/i'],
        'Insurance & visa' => ['icon' => 'shield-check', 'pattern' => '/insurance|visa|evacuation|rescue/i'],
        'Gear & extras' => ['icon' => 'briefcase', 'pattern' => '/gear|equipment|sleeping bag|jacket|duffel|kit|map|certificate|first aid|oximeter/i'],
        'Tips & personal' => ['icon' => 'banknotes', 'pattern' => '/tip|gratuit|personal|laundry|shower|wi-?fi|internet|charging|battery|souvenir/i'],
    ];

    /**
     * @param  array<int, mixed>|null  $items
     * @return array<int, array{title: string, icon: string, items: array<int, string>}>
     */
    public static function group(?array $items): array
    {
        $groups = [];

        foreach (self::clean($items) as $item) {
            $title = 'Other';
            foreach (self::CATEGORIES as $name => $category) {
                if (preg_match($category['pattern'], $item)) {
                    $title = $name;
                    break;
                }
            }
            $groups[$title][] = $item;
        }

        $order = array_merge(array_keys(self::CATEGORIES), ['Other']);
        uksort($groups, fn ($a, $b) => array_search($a, $order) <=> array_search($b, $order));

        return collect($groups)->map(fn (array $lines, string $title) => [
            'title' => $title,
            'icon' => self::CATEGORIES[$title]['icon'] ?? 'check-circle',
            'items' => $lines,
        ])->values()->all();
    }

    /**
     * @param  array<int, mixed>|null  $items
     * @return array<int, string>
     */
    public static function clean(?array $items): array
    {
        return collect($items ?? [])
            ->map(fn ($item) => trim(strip_tags(is_array($item) ? implode(' ', $item) : (string) $item)))
            ->filter()
            ->values()
            ->all();
    }
}
