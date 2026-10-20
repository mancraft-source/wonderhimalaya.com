<?php

namespace App\Services\WordPress;

use Illuminate\Support\Str;

/**
 * Turns marketing-style WordPress trip titles ("16 Days Everest Base Camp Trek",
 * "Everest Base Camp (11N/12D) Fixed Departure") into clean display titles and
 * professional, unique slugs.
 */
class TitleCleaner
{
    /** Words that already say what kind of trip it is. */
    protected const TYPE_WORDS = ['trek', 'trekking', 'tour', 'climbing', 'safari', 'expedition', 'package', 'hike', 'hiking', 'flight', 'circuit', 'rafting', 'trip', 'journey'];

    protected const SMALL_WORDS = ['a', 'an', 'and', 'as', 'at', 'by', 'for', 'in', 'of', 'on', 'or', 'the', 'to', 'via', 'with', 'from'];

    /** Decode entities and normalise whitespace / dashes. */
    public static function text(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(["\u{00A0}", "\u{2009}", "\u{202F}"], ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function isFixedDeparture(string $title): bool
    {
        return (bool) preg_match('/fixe?d?\s*departure/i', self::text($title));
    }

    /**
     * Clean display title: no duration noise, no "Fixed Departure" suffix.
     *
     * @param  bool  $appendTrek  add "Trek" when the title names no trip type (e.g. "Everest Base Camp")
     */
    public static function title(string $raw, bool $appendTrek = false): string
    {
        $t = self::text($raw);
        $d = '[\-–—:|]';

        // "Fixed Departure" (also glued: "(12N|13D)Fixed Departure").
        $t = preg_replace('/\s*fixe?d?\s*departure\b/iu', ' ', $t);
        // (11N/12D), 11N/12D, (12N|13D), (09N/10D)
        $t = preg_replace('/\(?\s*\d{1,2}\s*N\s*[\/|]\s*\d{1,2}\s*D\s*\)?/iu', ' ', $t);
        // " – 13 Days of Himalayan Spiritual Awakening" (marketing tail)
        $t = preg_replace('/\s*'.$d.'\s*\d{1,2}\s*days?\s+of\s+.*$/iu', '', $t);
        // "Bhutan 3/4 Days tour"
        $t = preg_replace('/\b\d{1,2}\s*\/\s*\d{1,2}\s*days?\b/iu', ' ', $t);
        // Leading "16 Days", "05 – Days-", "08-Day", "22days", "5 Days –", "22 Days :"
        $t = preg_replace('/^\s*\d{1,2}\s*'.$d.'?\s*days?\b\s*'.$d.'?\s*/iu', '', $t);
        // Leading bare numbers ("21 Island Peak")
        $t = preg_replace('/^\s*\d{1,2}\s+(?=[A-Za-z])/u', '', $t);
        // Trailing / inline "– 15 days", "-18 Days", "– 4 Days", "12 Days"
        $t = preg_replace('/\s*'.$d.'?\s*\b\d{1,2}\s*'.$d.'?\s*days?\b\s*/iu', ' ', $t);

        // Tidy separators left behind.
        $t = preg_replace('/\s*[\-–—:|]\s*$/u', '', $t);
        $t = preg_replace('/^\s*[\-–—:|]\s*/u', '', $t);
        $t = preg_replace('/\s+[\-–—]\s+/u', ' – ', $t);
        $t = preg_replace('/\(\s*\)/', '', $t);
        $t = trim((string) preg_replace('/\s+/u', ' ', $t), " \t\n\r\0\x0B-–—:|");

        $t = self::titleCase($t);

        if ($appendTrek && ! self::hasTypeWord($t)) {
            $t .= ' Trek';
        }

        return $t;
    }

    public static function hasTypeWord(string $title): bool
    {
        $words = preg_split('/[^a-z]+/', strtolower($title)) ?: [];

        return array_intersect($words, self::TYPE_WORDS) !== [];
    }

    /** Capitalise lower-case words, leaving acronyms and small words alone. */
    public static function titleCase(string $title): string
    {
        $words = explode(' ', $title);

        foreach ($words as $i => $word) {
            if ($word === '' || preg_match('/^\p{Lu}/u', $word)) {
                continue;
            }

            if ($i > 0 && in_array(strtolower($word), self::SMALL_WORDS, true)) {
                continue;
            }

            $words[$i] = Str::ucfirst($word);
        }

        return implode(' ', $words);
    }

    public static function slug(string $title): string
    {
        return Str::slug(str_replace(['&', '+'], [' and ', ' plus '], $title));
    }

    /**
     * Assign unique slugs deterministically. Items are processed oldest first
     * so the original/classic trip keeps the plain slug.
     *
     * @param  array<int, array{id: int, title: string, date: string, fixed: bool, days: int|null}>  $items
     * @param  array<int, string>  $reserved  slugs already taken elsewhere
     * @return array<int, string> id => slug
     */
    public static function uniqueSlugs(array $items, array $reserved = []): array
    {
        usort($items, fn ($a, $b) => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);

        $taken = array_fill_keys($reserved, true);
        $result = [];

        foreach ($items as $item) {
            $base = self::slug($item['title']) ?: 'trip-'.$item['id'];
            $candidates = [$base];

            if ($item['fixed']) {
                $candidates[] = $base.'-fixed-departure';
            }

            if ($item['days']) {
                $candidates[] = $base.'-'.$item['days'].'-days';
            }

            $slug = null;

            foreach ($candidates as $candidate) {
                if (! isset($taken[$candidate])) {
                    $slug = $candidate;
                    break;
                }
            }

            for ($n = 2; $slug === null; $n++) {
                $slug = isset($taken[$base.'-'.$n]) ? null : $base.'-'.$n;
            }

            $taken[$slug] = true;
            $result[$item['id']] = $slug;
        }

        return $result;
    }
}
