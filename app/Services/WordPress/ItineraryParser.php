<?php

namespace App\Services\WordPress;

/**
 * Extracts structured facts (altitude, distance, duration, meals,
 * accommodation) from WP Travel Engine itinerary titles and content.
 */
class ItineraryParser
{
    protected const MEALS = ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'];

    /** Labels of the small facts table WTE editors put at the top of a day. */
    protected const FACT_LABELS = ['accommodation', 'accomodation', 'meals', 'meal', 'distance', 'duration', 'activities', 'activity', 'altitude', 'max altitude', 'overnight', 'elevation', 'walking', 'trek', 'drive', 'flight', 'activity duration', 'activities / duration', 'walking hours', 'transport', 'transportation'];

    public function __construct(protected HtmlCleaner $cleaner) {}

    /**
     * @param  array<string, mixed>  $day  WTE itinerary item {title, content, meals_included, overnights, period, unit}
     * @return array{title: string, description: string, altitude_m: int|null, distance_km: float|null, duration: string|null, meals: array<int, string>, accommodation: string|null}
     */
    public function parse(array $day): array
    {
        $rawTitle = TitleCleaner::text($day['title'] ?? '');
        [$facts, $content] = $this->extractFactsTable((string) ($day['content'] ?? ''));
        $factText = implode(' ', array_map(fn ($k, $v) => "$k: $v", array_keys($facts), $facts));

        $altitude = self::altitude($rawTitle) ?? self::altitude($facts['altitude'] ?? $facts['max altitude'] ?? $facts['elevation'] ?? '');
        $distance = self::distance($rawTitle) ?? self::distance($facts['distance'] ?? '') ?? self::distance($factText);
        $duration = self::duration($rawTitle)
            ?? $this->factDuration($facts)
            ?? self::periodDuration($day);

        $meals = self::meals($day['meals_included'] ?? []);

        if ($meals === [] && ($facts['meals'] ?? $facts['meal'] ?? null)) {
            $meals = self::meals($facts['meals'] ?? $facts['meal']);
        }

        $accommodation = collect($day['overnights'] ?? [])->pluck('location')->filter()->implode(', ') ?: null;
        $accommodation ??= isset($facts['accommodation']) ? $facts['accommodation'] : ($facts['accomodation'] ?? $facts['overnight'] ?? null);

        return [
            'title' => self::cleanTitle($rawTitle),
            'description' => $this->cleaner->clean($content),
            'altitude_m' => $altitude,
            'distance_km' => $distance,
            'duration' => $duration ? mb_substr($duration, 0, 255) : null,
            'meals' => $meals,
            'accommodation' => $accommodation ? mb_substr(rtrim($accommodation, ' ,.;'), 0, 255) : null,
        ];
    }

    /** Drop "Day 3:" prefixes and the "Distance… Duration…" tail (kept in their own fields). */
    public static function cleanTitle(string $title): string
    {
        $title = TitleCleaner::text($title);
        $title = preg_replace('/^\s*day\s*\d+\s*[:.\-–]\s*/iu', '', $title);
        $title = preg_replace('/[.,;]?\s*(distance|duration|walking (hours|distance))\s*:.*$/iu', '', $title);
        $title = preg_replace('/[.,;]\s*(approx\.?\s*)?\d+(\.\d+)?\s*[–\-]\s*\d+(\.\d+)?\s*km\b.*$/iu', '', $title);

        return trim((string) $title, " \t\n\r\0\x0B:;,.-–");
    }

    /** Highest metre value, e.g. "Lukla (2860m/9383ft) to Phakding (2652m)" → 2860. */
    public static function altitude(?string $text): ?int
    {
        $text = (string) $text;
        $values = [];

        if (preg_match_all('/(\d{1,2}[,.]?\d{3}|\d{3,4})\s*(?:m|mtrs?|meters?|metres?)\b(?!\s*\/?\s*ft)/iu', $text, $m)) {
            $values = array_map(fn ($v) => (int) str_replace([',', '.'], '', $v), $m[1]);
        }

        // "Ghorepani (2,860)" — a bare number in brackets is metres on this site.
        if (preg_match_all('/\((\d{1,2},?\d{3})\)/u', $text, $m)) {
            $values = array_merge($values, array_map(fn ($v) => (int) str_replace(',', '', $v), $m[1]));
        }

        $values = array_filter($values, fn ($v) => $v >= 60 && $v <= 8849);

        return $values ? max($values) : null;
    }

    /** "Distance: 12–13 km" → 13.0 (upper bound), "8 km" → 8.0. */
    public static function distance(?string $text): ?float
    {
        $text = (string) $text;

        if (preg_match('/(?:distance\s*:?\s*)?(?:approx\.?\s*|about\s*|around\s*)?(\d+(?:\.\d+)?)\s*(?:[–\-]|to)?\s*(\d+(?:\.\d+)?)?\s*(?:km|kms|kilomet(?:er|re)s?)\b/iu', $text, $m)) {
            $value = (float) (! empty($m[2]) ? $m[2] : $m[1]);

            return $value > 0 && $value < 10000 ? round($value, 1) : null;
        }

        return null;
    }

    /** "Duration: 3-4 hrs" → "3-4 hrs"; "5–6 walking hours" → "5–6 hrs". */
    public static function duration(?string $text): ?string
    {
        $text = (string) $text;

        if (preg_match('/duration\s*:?\s*([^,;|]*?\d[^,;|]*?(?:hours?|hrs?|mins?|minutes?|days?))/iu', $text, $m)) {
            return self::tidyDuration($m[1]);
        }

        if (preg_match('/(\d+(?:\.\d+)?\s*(?:[–\-]|to)\s*\d+(?:\.\d+)?|\d+(?:\.\d+)?)\s*(?:walking\s+)?(hours?|hrs?)\b/iu', $text, $m)) {
            return self::tidyDuration($m[1].' hrs');
        }

        return null;
    }

    protected static function tidyDuration(string $value): string
    {
        $value = TitleCleaner::text($value);
        $value = preg_replace('/^\s*(?:activities\s*\/\s*)?duration\s*:\s*/iu', '', $value);
        $value = preg_replace('/\bhours?\b|\bhr\b/iu', 'hrs', $value);
        $value = preg_replace('/\s*(–|-)\s*/u', '$1', $value);

        $value = trim((string) $value, ' .');

        return preg_match('/^[\p{P}\s]*$/u', $value) ? '' : $value;
    }

    /** @param  array<string, string>  $facts */
    protected function factDuration(array $facts): ?string
    {
        foreach ($facts as $label => $value) {
            if (str_contains($label, 'duration') || str_contains($label, 'walking') || in_array($label, ['trek', 'drive', 'flight'], true)) {
                return self::tidyDuration($value) ?: null;
            }
        }

        return null;
    }

    protected static function periodDuration(array $day): ?string
    {
        $period = (float) ($day['period'] ?? 0);

        if ($period <= 0) {
            return null;
        }

        $unit = ($day['unit'] ?? 'hour') === 'minute' ? 'mins' : 'hrs';

        return rtrim(rtrim(number_format($period, 1), '0'), '.').' '.$unit;
    }

    /**
     * @param  array<int, string>|string  $meals
     * @return array<int, string>
     */
    public static function meals(array|string $meals): array
    {
        $text = strtolower(is_array($meals) ? implode(' ', array_map(fn ($m) => is_array($m) ? implode(' ', $m) : (string) $m, $meals)) : $meals);

        if (preg_match('/\b(all|three|3)\s*(times\s*)?meals?\b|\bfull board\b/', $text)) {
            return array_values(self::MEALS);
        }

        return array_values(array_filter(self::MEALS, fn ($label, $key) => str_contains($text, $key), ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Pull the leading key/value facts table out of the day's HTML.
     *
     * @return array{0: array<string, string>, 1: string}
     */
    public function extractFactsTable(string $html): array
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        if (! preg_match('/<table\b.*?<\/table>/is', $html, $table, PREG_OFFSET_CAPTURE)) {
            return [[], $html];
        }

        $facts = [];
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $table[0][0], $rows);

        foreach ($rows[1] as $row) {
            preg_match_all('/<t[dh]\b[^>]*>(.*?)<\/t[dh]>/is', $row, $cells);

            if (count($cells[1]) < 2) {
                continue;
            }

            $label = strtolower(trim(TitleCleaner::text($cells[1][0]), ' :'));
            $value = trim(TitleCleaner::text(implode(' ', array_slice($cells[1], 1))), ' ,');

            // "-", "N/A", "–" placeholders carry no information.
            if ($label !== '' && $value !== '' && ! preg_match('/^(?:[\p{P}\s]+|n\/?a|none|tbd)$/iu', $value)) {
                $facts[$label] = $value;
            }
        }

        $known = array_filter(array_keys($facts), fn ($label) => collect(self::FACT_LABELS)->contains(fn ($l) => str_contains($label, $l)));

        // Only treat it as a facts table when (nearly) all rows are known labels.
        if ($facts === [] || count($known) < max(1, count($facts) - 1)) {
            return [[], $html];
        }

        // Remove the table (and its wrapping <figure>) from the description.
        $start = $table[0][1];
        $end = $start + strlen($table[0][0]);
        $before = substr($html, 0, $start);
        $after = substr($html, $end);
        $before = preg_replace('/<figure\b[^>]*>\s*$/is', '', $before) ?? $before;
        $after = preg_replace('/^\s*<\/figure>/is', '', $after) ?? $after;

        return [$facts, $before.$after];
    }
}
