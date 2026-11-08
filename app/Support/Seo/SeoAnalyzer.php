<?php

namespace App\Support\Seo;

use Illuminate\Support\Str;

/**
 * Yoast-style on-page checks. Each check is good (green), ok (orange) or bad (red);
 * the score is the share of points earned (good = 1, ok = ½).
 */
final class SeoAnalyzer
{
    public const GOOD = 'good';

    public const OK = 'ok';

    public const BAD = 'bad';

    /**
     * @param  array{title?: ?string, description?: ?string, slug?: ?string, keyphrase?: ?string, html?: ?string}  $input
     * @return array{score: int, checks: array<int, array{status: string, label: string}>}
     */
    public static function analyze(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $slug = (string) ($input['slug'] ?? '');
        $keyphrase = Str::lower(trim((string) ($input['keyphrase'] ?? '')));
        $html = (string) ($input['html'] ?? '');
        $text = Str::squish(html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $words = $text === '' ? 0 : str_word_count($text);

        $checks = [];
        $add = function (string $status, string $label) use (&$checks): void {
            $checks[] = ['status' => $status, 'label' => $label];
        };

        $titleLength = mb_strlen($title);
        match (true) {
            $titleLength === 0 => $add(self::BAD, 'The page has no SEO title.'),
            $titleLength < 30 => $add(self::OK, "The SEO title is short ({$titleLength} characters). Use the space to add a keyword or benefit."),
            $titleLength <= 60 => $add(self::GOOD, "The SEO title length is good ({$titleLength} characters)."),
            $titleLength <= 70 => $add(self::OK, "The SEO title may be cut off in search results ({$titleLength} of ~60 characters)."),
            default => $add(self::BAD, "The SEO title is too long ({$titleLength} characters) and will be cut off."),
        };

        $descriptionLength = mb_strlen($description);
        match (true) {
            $descriptionLength === 0 => $add(self::BAD, 'No meta description: search engines will pick text from the page instead.'),
            $descriptionLength < 120 => $add(self::OK, "The meta description is short ({$descriptionLength} characters). Aim for 120–160."),
            $descriptionLength <= 160 => $add(self::GOOD, "The meta description length is good ({$descriptionLength} characters)."),
            default => $add(self::OK, "The meta description is long ({$descriptionLength} characters) and may be cut off after ~160."),
        };

        if ($keyphrase === '') {
            $add(self::OK, 'No focus keyphrase set. Add the search term this page should rank for to get keyphrase checks.');
        } else {
            $contains = fn (string $haystack): bool => str_contains(Str::lower($haystack), $keyphrase);
            $add($contains($title) ? self::GOOD : self::BAD, $contains($title) ? 'The focus keyphrase appears in the SEO title.' : 'Add the focus keyphrase to the SEO title.');
            $add($contains($description) ? self::GOOD : self::BAD, $contains($description) ? 'The focus keyphrase appears in the meta description.' : 'Add the focus keyphrase to the meta description.');

            $slugWords = collect(explode('-', Str::slug($keyphrase)))->filter(fn ($w) => strlen($w) > 2);
            $inSlug = $slugWords->isNotEmpty() && $slugWords->every(fn ($w) => str_contains($slug, $w));
            $add($inSlug ? self::GOOD : self::OK, $inSlug ? 'The focus keyphrase appears in the URL.' : 'The URL (slug) does not contain the focus keyphrase.');

            if ($html !== '') {
                preg_match('/<p[^>]*>(.*?)<\/p>/is', $html, $first);
                $intro = strip_tags($first[1] ?? Str::words($text, 60, ''));
                $add($contains($intro) ? self::GOOD : self::OK, $contains($intro) ? 'The focus keyphrase appears in the introduction.' : 'Use the focus keyphrase in the first paragraph.');

                $occurrences = substr_count(Str::lower($text), $keyphrase);
                $density = $words > 0 ? $occurrences * max(1, str_word_count($keyphrase)) / $words * 100 : 0;
                match (true) {
                    $occurrences === 0 => $add(self::BAD, 'The focus keyphrase does not appear in the content.'),
                    $density > 3 => $add(self::OK, 'The focus keyphrase is used very often ('.round($density, 1).'%). Avoid keyword stuffing.'),
                    default => $add(self::GOOD, "The focus keyphrase appears {$occurrences} ".Str::plural('time', $occurrences).' in the content.'),
                };
            }
        }

        if ($html !== '' || $words > 0) {
            match (true) {
                $words >= 300 => $add(self::GOOD, "The content has {$words} words."),
                $words >= 150 => $add(self::OK, "The content has {$words} words. 300+ words usually rank better."),
                default => $add(self::BAD, "The content is thin ({$words} words)."),
            };

            preg_match_all('/<img\b[^>]*>/i', $html, $images);
            $missingAlt = collect($images[0])->reject(fn ($img) => preg_match('/\balt="[^"]+"/i', $img))->count();
            if ($images[0] !== []) {
                $add($missingAlt === 0 ? self::GOOD : self::BAD, $missingAlt === 0 ? 'All images in the content have alt text.' : "{$missingAlt} ".Str::plural('image', $missingAlt).' in the content '.($missingAlt === 1 ? 'has' : 'have').' no alt text.');
            }

            $host = parse_url(config('app.url'), PHP_URL_HOST);
            preg_match_all('/<a\b[^>]*href="([^"]+)"/i', $html, $links);
            $internal = collect($links[1])->filter(fn ($href) => str_starts_with($href, '/') || ($host && str_contains($href, $host)))->count();
            $add($internal > 0 ? self::GOOD : self::OK, $internal > 0 ? "The content links to {$internal} other ".Str::plural('page', $internal).' on the site.' : 'Add internal links to related trips, regions or guides.');

            if ($words >= 300) {
                $hasSubheadings = preg_match('/<h[23]\b/i', $html) === 1;
                $add($hasSubheadings ? self::GOOD : self::OK, $hasSubheadings ? 'The content is broken up with subheadings.' : 'Long content without subheadings: add H2/H3 headings.');
            }
        }

        $points = collect($checks)->sum(fn ($c) => ['good' => 1, 'ok' => 0.5, 'bad' => 0][$c['status']]);
        $order = ['bad' => 0, 'ok' => 1, 'good' => 2];
        usort($checks, fn ($a, $b) => $order[$a['status']] <=> $order[$b['status']]);

        return ['score' => (int) round($points / max(1, count($checks)) * 100), 'checks' => $checks];
    }

    /** Traffic-light colour for a score. */
    public static function rating(?int $score): string
    {
        return match (true) {
            $score === null => 'gray',
            $score >= 75 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
    }
}
