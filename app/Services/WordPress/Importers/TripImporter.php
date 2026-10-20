<?php

namespace App\Services\WordPress\Importers;

use App\Enums\DepartureStatus;
use App\Enums\PublishStatus;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Difficulty;
use App\Models\Tag;
use App\Models\Tier;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\ItineraryParser;
use App\Services\WordPress\TitleCleaner;
use App\Services\WordPress\WordPressSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripImporter extends Importer
{
    /** Canonical labels for WTE "trip_info" facts. */
    protected const FACT_LABELS = ['Accomodation' => 'Accommodation', 'Transportation' => 'Transport'];

    /**
     * @param  array<int, int>  $ids  limit to these WP ids
     */
    public function import(array $ids = []): ImportStats
    {
        $trips = collect($this->source->trips())
            ->when($ids, fn ($c) => $c->only($ids))
            ->sortBy(fn ($t) => [$t['date_gmt'] ?? $t['date'], $t['id']]);

        foreach ($trips as $wp) {
            $this->log('Trip '.$wp['id'].': '.TitleCleaner::text($wp['title']['rendered'] ?? ''));
            DB::transaction(fn () => $this->importTrip($wp));
        }

        $this->linkRelated();

        return $this->stats;
    }

    public function importTrip(array $wp): Trip
    {
        $id = (int) $wp['id'];
        $detail = $this->source->tripDetail($id) + $wp;
        $cleaner = $this->cleaner();

        $trip = $this->locate(Trip::class, $id);
        $facts = $this->facts($detail['trip_info'] ?? []);
        $days = array_values(array_map(fn ($day) => (new ItineraryParser($cleaner))->parse($day), $detail['itineraries'] ?? []));
        $overview = $cleaner->clean($detail['overview'] ?? $wp['description'] ?? $wp['content']['rendered'] ?? '');
        $durationDays = $this->int(Arr::get($detail, 'duration.period') ?? Arr::get($wp, 'duration.days'));
        $durationNights = $this->int(Arr::get($detail, 'duration.nights') ?? Arr::get($wp, 'duration.nights'), allowZero: true);
        $fixed = $this->source->isFixedDeparture($wp);
        $title = $this->source->tripTitle($wp);
        $prices = $this->prices($id);

        $altitude = ItineraryParser::altitude($facts['Maximum Altitude'] ?? $facts['Max Altitude'] ?? null)
            ?? (collect($days)->pluck('altitude_m')->filter()->max() ?: null);

        $trip->fill([
            'trip_code' => $this->tripCode($detail['trip_code'] ?? $wp['code'] ?? null, $trip->id, $id),
            'title' => Str::limit($title, 250, ''),
            'slug' => $this->uniqueSlug(Trip::class, $this->source->tripSlugs()[$id], $trip->id),
            'subtitle' => $this->subtitle($durationDays, $fixed),
            'excerpt' => HtmlCleaner::excerpt($overview, 40) ?: null,
            'overview' => $overview ?: null,
            'duration_days' => $durationDays,
            'duration_nights' => $durationNights,
            'max_altitude_m' => $altitude,
            'group_min' => $this->int(Arr::get($detail, 'participants.min') ?? $wp['min_pax'] ?? null),
            'group_max' => $this->int(Arr::get($detail, 'participants.max') ?? $wp['max_pax'] ?? null),
            'min_age' => $this->int(Arr::get($detail, 'age_limit.min')),
            'best_season' => $this->limit($facts['Best Season'] ?? null),
            'accommodation' => $this->limit($facts['Accommodation'] ?? null),
            'meals' => $this->limit($facts['Meals'] ?? null),
            'transport' => $this->limit($facts['Transport'] ?? null),
            'start_point' => $this->limit($facts['Arrival City'] ?? null),
            'end_point' => $this->limit($facts['Departure City'] ?? null),
            'difficulty_id' => $this->difficultyId($wp),
            'destination_id' => $this->primaryDestinationId($wp),
            'highlights' => $this->strings($detail['highlights'] ?? []) ?: null,
            'includes' => $this->strings($detail['cost_includes'] ?? []) ?: null,
            'excludes' => $this->strings($detail['cost_excludes'] ?? []) ?: null,
            'facts' => $facts ?: null,
            'videos' => $this->videos($detail['video_gallery'] ?? []) ?: null,
            'price_from' => $this->priceFrom($wp, $prices),
            'sale_price' => ! empty($wp['has_sale']) && (float) ($wp['sale_price'] ?? 0) > 0 ? (float) $wp['sale_price'] : null,
            'currency' => strtoupper((string) (Arr::get($wp, 'currency.code') ?: 'USD')),
            'is_fixed_departure' => $fixed,
            'is_featured' => in_array($wp['slug'], WordPressSource::FEATURED_TRIPS, true),
            'is_popular' => in_array($wp['slug'], WordPressSource::POPULAR_TRIPS, true),
            'is_top_selling' => in_array($wp['slug'], WordPressSource::TOP_SELLING_TRIPS, true),
            'status' => PublishStatus::Published,
            'published_at' => $this->date($wp),
            'seo' => WordPressSource::seo($detail, $wp['title']['rendered'] ?? null),
            'wp_id' => $id,
            'wp_slug' => $wp['slug'],
        ]);

        $trip->save();
        $this->stats->record('trips', $trip);

        $trip->destinations()->sync(Destination::whereIn('wp_id', $wp['destination'] ?? [])->pluck('id'));
        $trip->activities()->sync(Activity::whereIn('wp_id', $wp['activities'] ?? [])->pluck('id'));
        $trip->tiers()->sync(Tier::whereIn('wp_id', $wp['trip_types'] ?? [])->pluck('id'));
        $trip->tags()->sync(Tag::whereIn('slug', $this->source->termSlugs($wp, 'trip_tag'))->pluck('id'));

        $this->syncDays($trip, $days);
        $this->syncFaqs($trip, $detail, $cleaner);
        $this->syncPrices($trip, $prices);
        $this->syncDepartures($trip, $id, $durationDays);
        $this->syncMedia($trip, $wp, $detail);

        return $trip;
    }

    /** @return array<string, string> label => content */
    protected function facts(array $info): array
    {
        $facts = [];

        foreach ($info as $row) {
            $label = TitleCleaner::text($row['label'] ?? '');
            $value = TitleCleaner::text($row['content'] ?? '');

            if ($label !== '' && $value !== '') {
                $facts[self::FACT_LABELS[$label] ?? $label] = $value;
            }
        }

        return $facts;
    }

    /** @return array<int, string> */
    protected function strings(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r?\n/', $value) ?: [];
        }

        return collect(Arr::wrap($value))
            ->map(function ($item) {
                if (is_array($item)) {
                    $item = $item['highlight_text'] ?? $item['text'] ?? $item['title'] ?? $item['content'] ?? $item['label'] ?? implode(' ', array_filter($item, 'is_string'));
                }

                $text = TitleCleaner::text((string) $item);

                return trim((string) preg_replace('/^[\s•\-\*–·✓✔]+/u', '', $text));
            })
            ->filter(fn ($s) => $s !== '')
            ->values()->all();
    }

    /** @return array<int, string> */
    protected function videos(array $items): array
    {
        return collect($items)->map(function ($v) {
            if (is_string($v)) {
                return $v;
            }

            $url = $v['url'] ?? $v['link'] ?? null;

            if (! $url && ! empty($v['id'])) {
                $type = strtolower($v['type'] ?? 'youtube');
                $url = $type === 'vimeo' ? 'https://vimeo.com/'.$v['id'] : 'https://www.youtube.com/watch?v='.$v['id'];
            }

            return $url;
        })->filter()->values()->all();
    }

    protected function int(mixed $value, bool $allowZero = false): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 || ($allowZero && $value === 0) ? $value : null;
    }

    protected function limit(?string $value, int $length = 255): ?string
    {
        return $value === null || $value === '' ? null : Str::limit($value, $length - 3);
    }

    protected function subtitle(?int $days, bool $fixed): ?string
    {
        $parts = array_filter([
            $days ? $days.' '.Str::plural('day', $days) : null,
            $fixed ? 'Fixed departure' : null,
        ]);

        return $parts ? implode(' · ', $parts) : null;
    }

    protected function tripCode(?string $code, ?int $tripId, int $wpId): ?string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $taken = Trip::withTrashed()->where('trip_code', $code)->when($tripId, fn ($q) => $q->whereKeyNot($tripId))->exists();

        if ($taken) {
            $this->stats->note("Duplicate trip code {$code} on WP #{$wpId}; stored as {$code}-{$wpId}.");

            return $code.'-'.$wpId;
        }

        return $code;
    }

    protected function difficultyId(array $wp): ?int
    {
        $id = Arr::first($wp['difficulty'] ?? []);

        return $id ? Difficulty::where('wp_id', $id)->value('id') : null;
    }

    /** The most specific destination: a region (child) rather than the country. */
    protected function primaryDestinationId(array $wp): ?int
    {
        $destinations = Destination::whereIn('wp_id', $wp['destination'] ?? [])->get();

        return $destinations->sortByDesc(fn ($d) => $d->parent_id ? 1 : 0)->first()?->id;
    }

    // --------------------------------------------------------------- prices

    /**
     * Packages → price rows (primary traveller category only).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function prices(int $wpId): array
    {
        $rows = [];
        $tiers = Tier::pluck('id', 'slug');

        foreach ($this->source->tripPackages($wpId) as $package) {
            $label = $this->packageLabel((string) ($package['name'] ?? ''));
            $categories = collect($package['traveler_categories'] ?? []);
            $category = $categories->first(fn ($c) => ! empty($c['is_primary']) && $c['price'] !== '')
                ?? $categories->first(fn ($c) => strtolower($c['label'] ?? '') === 'adult' && $c['price'] !== '')
                ?? $categories->first(fn ($c) => ($c['price'] ?? '') !== '');

            if (! $category || (float) $category['price'] <= 0) {
                continue;
            }

            $tierSlug = collect(['luxury', 'premium', 'standard'])->first(fn ($t) => str_contains(strtolower($label), $t));
            [$min, $max] = $this->paxBand($label, (int) ($category['min_pax'] ?? 0), $category['max_pax'] ?? null);
            $sale = ! empty($category['has_sale']) || (is_numeric($category['sale_price'] ?? null) && (float) $category['sale_price'] > 0)
                ? (float) $category['sale_price'] : null;

            $groups = collect($category['group_pricing'] ?? [])->filter(fn ($g) => is_numeric($g['price'] ?? null));

            if (! empty($category['has_group_pricing']) && $groups->isNotEmpty()) {
                foreach ($groups as $g) {
                    $rows[] = [
                        'tier_id' => $tierSlug ? ($tiers[$tierSlug] ?? null) : null,
                        'label' => $label,
                        'pax_min' => max(1, (int) ($g['from'] ?? $min)),
                        'pax_max' => is_numeric($g['to'] ?? null) ? (int) $g['to'] : null,
                        'price' => (float) $g['price'],
                        'sale_price' => null,
                    ];
                }

                continue;
            }

            $rows[] = [
                'tier_id' => $tierSlug ? ($tiers[$tierSlug] ?? null) : null,
                'label' => $label,
                'pax_min' => $min,
                'pax_max' => $max,
                'price' => (float) $category['price'],
                'sale_price' => $sale && $sale > 0 ? $sale : null,
            ];
        }

        return $rows;
    }

    protected function packageLabel(string $name): string
    {
        $name = TitleCleaner::text($name);
        $name = preg_replace(['/\bpaclage\b/i', '/\bpackages\b/i', '/\s*\/\s*/'], ['Package', 'Package', ' / '], $name);
        $name = preg_replace_callback('/\b([A-Z]{2,})([a-z]+)\b/', fn ($m) => ucfirst(strtolower($m[0])), (string) $name);

        return Str::limit(TitleCleaner::titleCase((string) $name), 250, '') ?: 'Standard';
    }

    /** @return array{0: int, 1: int|null} */
    protected function paxBand(string $label, int $minPax, mixed $maxPax): array
    {
        $l = strtolower($label);
        $max = is_numeric($maxPax) && (int) $maxPax > 0 ? (int) $maxPax : null;

        return match (true) {
            str_contains($l, 'single') && str_contains($l, 'couple') => [1, 2],
            str_contains($l, 'couple') && str_contains($l, 'group') => [2, $max],
            str_contains($l, 'single') => [1, 1],
            str_contains($l, 'couple') => [2, 2],
            str_contains($l, 'group') => [max(2, $minPax), $max],
            default => [max(1, $minPax), $max],
        };
    }

    protected function priceFrom(array $wp, array $prices): ?float
    {
        $price = (float) ($wp['price'] ?? 0);

        if ($price > 0) {
            return $price;
        }

        $min = collect($prices)->pluck('price')->filter()->min();

        return $min ? (float) $min : null;
    }

    protected function syncPrices(Trip $trip, array $prices): void
    {
        $trip->prices()->delete();

        foreach (array_values($prices) as $i => $row) {
            $trip->prices()->create($row + ['sort' => $i + 1]);
        }

        $this->stats->add('trip_prices', 'created', count($prices));
    }

    // ----------------------------------------------------------- departures

    /**
     * WTE exposes availability per package. A rolling "any day" calendar is
     * not a fixed departure, so only sparse date lists are imported.
     */
    protected function syncDepartures(Trip $trip, int $wpId, ?int $days): void
    {
        $dates = [];

        foreach ($this->source->tripDates($wpId) as $package) {
            foreach (($package['dates'] ?? []) as $date => $info) {
                $dates[$date] ??= $info;
            }
        }

        if ($dates === []) {
            return;
        }

        ksort($dates);
        $first = CarbonImmutable::parse(array_key_first($dates));
        $last = CarbonImmutable::parse(array_key_last($dates));
        $span = max(1, $first->diffInDays($last) + 1);

        if (count($dates) / $span > 0.25) {
            $this->stats->add('trip_departures', 'skipped');

            return; // rolling daily availability, not fixed dates
        }

        foreach ($dates as $date => $info) {
            $start = CarbonImmutable::parse($date);
            $price = collect($info['pricing'] ?? [])->first(fn ($p) => ! empty($p['is_primary']))['price'] ?? null;
            $seats = (int) ($info['capacity'] ?? $info['seats'] ?? 12);
            $left = isset($info['seats_left']) ? (int) $info['seats_left'] : $seats;

            $departure = TripDeparture::firstOrNew(['trip_id' => $trip->id, 'start_date' => $start->toDateString()]);
            $departure->fill([
                'end_date' => $days ? $start->addDays($days - 1)->toDateString() : null,
                'price' => is_numeric($price) ? (float) $price : null,
                'seats_total' => max(1, $seats),
                'seats_booked' => $departure->exists ? $departure->seats_booked : max(0, $seats - $left),
                'status' => $departure->exists ? $departure->status : ($left <= 0 ? DepartureStatus::SoldOut : DepartureStatus::Available),
            ])->save();

            $this->stats->record('trip_departures', $departure);
        }
    }

    // ----------------------------------------------------------- children

    /** @param  array<int, array<string, mixed>>  $days */
    protected function syncDays(Trip $trip, array $days): void
    {
        $trip->days()->delete();

        foreach ($days as $i => $day) {
            $trip->days()->create([
                'day_number' => $i + 1,
                'title' => Str::limit($day['title'] !== '' ? $day['title'] : 'Day '.($i + 1), 495, ''),
                'description' => $day['description'] ?: null,
                'altitude_m' => $day['altitude_m'],
                'distance_km' => $day['distance_km'],
                'duration' => $day['duration'],
                'meals' => $day['meals'] ?: null,
                'accommodation' => $day['accommodation'],
                'sort' => $i + 1,
            ]);
        }

        $this->stats->add('trip_days', 'created', count($days));
    }

    protected function syncFaqs(Trip $trip, array $detail, HtmlCleaner $cleaner): void
    {
        $faqs = collect(Arr::get($detail, 'faqs_data.categories', []))
            ->flatMap(fn ($cat) => $cat['faqs'] ?? [])
            ->whenEmpty(fn () => collect($detail['faqs'] ?? []))
            ->map(fn ($f) => [
                'question' => TitleCleaner::text($f['question'] ?? $f['title'] ?? ''),
                'answer' => $cleaner->clean($f['answer'] ?? $f['content'] ?? ''),
            ])
            ->filter(fn ($f) => $f['question'] !== '' && $f['answer'] !== '')
            ->unique('question')
            ->values();

        $trip->faqs()->delete();

        foreach ($faqs as $i => $faq) {
            $trip->faqs()->create([
                'question' => Str::limit($faq['question'], 495, ''),
                'answer' => $faq['answer'],
                'sort' => $i + 1,
            ]);
        }

        $this->stats->add('trip_faqs', 'created', $faqs->count());
    }

    public function syncMedia(Trip $trip, array $wp, array $detail): void
    {
        if (! $this->media->enabled()) {
            return;
        }

        if ($hero = $this->source->mediaItem((int) ($detail['featured_media'] ?? $wp['featured_media'] ?? 0))) {
            $this->media->attach($trip, 'hero', $hero['url'], $hero['alt'] ?: $trip->title, $hero['id']);
        }

        $this->media->sync($trip, 'gallery', collect($detail['gallery'] ?? [])->map(fn ($g) => [
            'url' => $g['url'] ?? null, 'alt' => $g['alt'] ?? null, 'id' => $g['id'] ?? null,
        ])->filter(fn ($g) => $g['url'])->values()->all());

        if ($map = Arr::first(Arr::get($detail, 'trip_map.images', []))) {
            $this->media->attach($trip, 'map', $map['url'] ?? null, $map['alt'] ?? $trip->title.' map', $map['id'] ?? null);
        }
    }

    /** Attach media and mirror inline images for an already-imported trip. */
    public function importMedia(Trip $trip): void
    {
        $wp = $this->source->trips()[$trip->wp_id] ?? null;

        if (! $wp) {
            return;
        }

        $this->syncMedia($trip, $wp, $this->source->tripDetail((int) $trip->wp_id) + $wp);
        $cleaner = $this->cleaner();
        $trip->update(['overview' => $cleaner->rewriteUrls($trip->overview) ?: null]);

        foreach ($trip->days as $day) {
            $day->update(['description' => $cleaner->rewriteUrls($day->description) ?: null]);
        }

        foreach ($trip->faqs as $faq) {
            $faq->update(['answer' => $cleaner->rewriteUrls($faq->answer)]);
        }

        $this->stats->add('trips (media)', 'updated');
    }

    /** Auto-link up to 3 trips sharing the primary destination (then the country). */
    public function linkRelated(): void
    {
        $trips = Trip::with('destination')->whereNotNull('wp_id')->get();

        foreach ($trips as $trip) {
            if (! $trip->destination_id) {
                continue;
            }

            $score = fn (Trip $other) => [
                $other->destination_id === $trip->destination_id ? 0 : 1,
                $other->is_fixed_departure === $trip->is_fixed_departure ? 0 : 1,
                $other->is_featured ? 0 : 1,
                abs(($other->duration_days ?? 0) - ($trip->duration_days ?? 0)),
                $other->id,
            ];

            $related = $trips
                ->reject(fn (Trip $o) => $o->id === $trip->id)
                ->filter(fn (Trip $o) => $o->destination_id === $trip->destination_id
                    || ($trip->destination?->parent_id && $o->destination?->parent_id === $trip->destination->parent_id))
                ->sortBy($score)
                ->take(3)
                ->pluck('id');

            $trip->related()->sync($related);
            $this->stats->add('related_trips', 'created', $related->count());
        }
    }
}
