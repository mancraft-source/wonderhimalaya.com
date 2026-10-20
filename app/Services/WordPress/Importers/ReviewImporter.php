<?php

namespace App\Services\WordPress\Importers;

use App\Models\Testimonial;
use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\TitleCleaner;
use Carbon\CarbonImmutable;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/** Google reviews rendered by the Trustindex widget on the homepage. */
class ReviewImporter extends Importer
{
    public function import(): ImportStats
    {
        try {
            $html = $this->source->homeHtml();
        } catch (\Throwable $e) {
            $this->stats->note('Reviews skipped: homepage HTML unavailable ('.$e->getMessage().').');

            return $this->stats;
        }

        $reviews = self::parse($html);

        if ($reviews === []) {
            $this->stats->note('Reviews skipped: no Trustindex reviews found in the homepage HTML.');
        }

        foreach ($reviews as $review) {
            $testimonial = Testimonial::where('source', $review['source'])
                ->where('author_name', $review['author_name'])
                ->when($review['reviewed_at'], fn ($q, $d) => $q->whereDate('reviewed_at', $d), fn ($q) => $q->whereNull('reviewed_at'))
                ->first() ?? new Testimonial;

            $testimonial->fill([
                'source' => $review['source'],
                'author_name' => $review['author_name'],
                'reviewed_at' => $review['reviewed_at'],
                'body' => $review['body'],
                'rating' => $review['rating'],
                'is_featured' => true,
                'is_published' => $testimonial->exists ? $testimonial->is_published : true,
            ])->save();

            $this->stats->record('testimonials', $testimonial);
        }

        return $this->stats;
    }

    /**
     * @return array<int, array{author_name: string, body: string, rating: int, reviewed_at: ?string, source: string}>
     */
    public static function parse(string $html): array
    {
        // The review text is wrapped in <!-- R-CONTENT --> markers; keep it.
        $html = str_replace('<!-- R-CONTENT -->', '', $html);
        $dom = (new HtmlCleaner)->load($html);
        $xpath = new DOMXPath($dom);
        $reviews = [];

        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " ti-review-item ")]') ?: [] as $item) {
            /** @var DOMElement $item */
            $name = TitleCleaner::text($xpath->query('.//*[contains(@class,"ti-name")]', $item)->item(0)?->textContent);
            $textNode = $xpath->query('.//*[contains(@class,"ti-review-content")]', $item)->item(0);
            $body = $textNode ? HtmlCleaner::text($dom->saveHTML($textNode)) : '';

            if ($name === '' || $body === '') {
                continue;
            }

            $full = 0.0;

            foreach ($xpath->query('.//*[contains(@class,"ti-star")]', $item) ?: [] as $star) {
                /** @var DOMElement $star */
                $src = $star->getAttribute('data-imgurl') ?: $star->getAttribute('src');
                $full += match (true) {
                    str_ends_with($src, '/f.svg') => 1,
                    str_ends_with($src, '/h.svg') => 0.5,
                    default => 0,
                };
            }

            $time = (int) $item->getAttribute('data-time');
            $source = preg_match('/source-([A-Za-z]+)/', $item->getAttribute('class'), $m) ? Str::lower($m[1]) : 'google';

            $reviews[] = [
                'author_name' => Str::limit($name, 250, ''),
                'body' => $body,
                'rating' => (int) max(1, min(5, round($full ?: 5))),
                'reviewed_at' => $time > 0 ? CarbonImmutable::createFromTimestamp($time)->toDateString() : null,
                'source' => $source,
            ];
        }

        return collect($reviews)->unique(fn ($r) => $r['author_name'].'|'.$r['body'])->values()->all();
    }
}
