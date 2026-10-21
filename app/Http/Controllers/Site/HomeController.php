<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Support\Content;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomeController extends Controller
{
    /**
     * Curated hero slides (trip slug => caption). Placeholder photography from the
     * current site until the new shoot arrives; missing trips are skipped.
     *
     * @var array<string, array{eyebrow: string, title: string}>
     */
    private const HERO_SLIDES = [
        'gokyo-lake-trek' => ['eyebrow' => 'Everest Region · 5,357 m', 'title' => 'Gokyo Lakes & Renjo La'],
        'annapurna-base-camp-trek' => ['eyebrow' => 'Annapurna Region · 4,130 m', 'title' => 'Annapurna Base Camp'],
        'tibet-overland-tour' => ['eyebrow' => 'Tibet · the roof of the world', 'title' => 'Tibet Overland'],
        'langtang-gosainkunda-circuit-trek' => ['eyebrow' => 'Langtang Region · 4,610 m', 'title' => 'Gosaikunda Sacred Lakes'],
    ];

    /**
     * "Travel by feeling" panels, keyed by activity slug.
     *
     * @var array<string, array{title: string, line: string}>
     */
    private const MOODS = [
        'guided-trek' => ['title' => 'Classic trails', 'line' => 'Tea-house treks to the great base camps'],
        'peak-climbing' => ['title' => 'Summits', 'line' => 'Island, Mera, Lobuche & trekking peaks'],
        'heli-tour' => ['title' => 'Above it all', 'line' => 'Helicopter journeys to the high valleys'],
        'city-tour' => ['title' => 'Living culture', 'line' => 'Temples, durbar squares & festivals'],
        'jungle-safari' => ['title' => 'Into the wild', 'line' => 'Rhinos and tigers in Chitwan'],
        'paragliding' => ['title' => 'Pure adrenaline', 'line' => 'Soar over Phewa Lake & Pokhara'],
    ];

    public function __invoke(): View
    {
        $home = Content::home();
        $trips = Trip::published()->with(['destination', 'difficulty', 'media'])->orderBy('sort')->latest('published_at')->get();

        $picked = collect($home['signature_trip_ids'])->map(fn ($id) => $trips->firstWhere('id', (int) $id))->filter();
        $signature = $picked->isNotEmpty() ? $picked->values() : $trips->where('is_featured', true)
            ->merge($trips->where('is_popular', true))
            ->merge($trips->where('is_fixed_departure', true))
            ->unique('id')
            ->filter(fn (Trip $t) => $t->hasMedia('hero'))
            ->take(8)
            ->values();

        $regions = Destination::query()
            ->whereHas('parent', fn ($q) => $q->where('slug', 'nepal'))
            ->withCount(['trips' => fn ($q) => $q->published()])
            ->with(['media', 'trips' => fn ($q) => $q->published()->with('media')])
            ->orderBy('sort')
            ->get()
            ->where('trips_count', '>', 0)
            ->sortByDesc('trips_count')
            ->values();

        $countries = Destination::whereNull('parent_id')->with('media')->withCount(['trips' => fn ($q) => $q->published()])->orderBy('sort')->get();

        $departures = TripDeparture::query()
            ->with(['trip.destination', 'trip.media', 'trip.difficulty'])
            ->whereHas('trip', fn ($q) => $q->published())
            ->whereDate('start_date', '>=', today())
            ->orderBy('start_date')
            ->limit(6)
            ->get();

        return view('site.home', [
            'seo' => Seo::make(
                'Wonder Himalaya — Treks, Tours & Expeditions in Nepal, Tibet & Bhutan',
                'Locally owned Kathmandu operator crafting guided treks, peak climbs and cultural journeys across Nepal, Tibet and Bhutan. Everest, Annapurna, Manaslu, Langtang and beyond.',
            ),
            'home' => $home,
            'heroSlides' => $this->heroSlides($trips, $home['hero_slides']),
            'moods' => $this->moods($home['moods']),
            'collage' => $this->collage($trips, $home['collage_trip_ids'], $signature),
            'signature' => $signature,
            'featureTrip' => ($home['feature_trip_id'] ? $trips->firstWhere('id', (int) $home['feature_trip_id']) : null)
                ?? $trips->firstWhere('is_trip_of_month', true) ?? $trips->firstWhere('is_top_selling', true),
            'isTripOfMonth' => $trips->contains('is_trip_of_month', true),
            'departures' => $departures,
            'fixedTrips' => $trips->where('is_fixed_departure', true)->sortBy(fn (Trip $t) => $t->displayPrice() ?? PHP_INT_MAX)->take(6)->values(),
            'regions' => $regions,
            'regionMedia' => $regions->mapWithKeys(fn (Destination $region) => [
                $region->id => $region->getFirstMedia('hero') ?? $this->largestHero($region->trips),
            ]),
            'countries' => $countries,
            'testimonials' => Testimonial::where('is_published', true)->orderByDesc('is_featured')->latest('reviewed_at')->limit(12)->get(),
            'posts' => Post::published()->with(['media', 'category'])->latest('published_at')->limit(3)->get(),
            'stats' => Setting::get('stats', []),
            'reviews' => Setting::get('reviews', []),
        ]);
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @return array<int, array{media: Media, eyebrow: string, title: string, url: string}>
     */
    private function heroSlides(Collection $trips, array $custom = []): array
    {
        $slides = [];

        foreach ($custom as $row) {
            $trip = $trips->firstWhere('id', (int) ($row['trip_id'] ?? 0));
            $image = Content::image($row['image'] ?? null);
            $media = $trip?->getFirstMedia('hero');
            if (! $trip || (! $image && ! $media)) {
                continue;
            }
            $slides[] = [
                'media' => $image ? null : $media,
                'src' => $image,
                'eyebrow' => $row['eyebrow'] ?? ($trip->destination?->name ?? ''),
                'title' => $row['title'] ?? $trip->title,
                'url' => url($trip->publicPath()),
            ];
        }

        if ($slides !== []) {
            return $slides;
        }

        foreach (self::HERO_SLIDES as $slug => $caption) {
            $trip = $trips->firstWhere('slug', $slug);
            if ($media = $trip?->getFirstMedia('hero')) {
                $slides[] = $caption + ['media' => $media, 'src' => null, 'url' => url($trip->publicPath())];
            }
        }

        if ($slides === []) {
            $slides = $trips->filter(fn (Trip $t) => $t->hasMedia('hero'))->take(3)
                ->map(fn (Trip $t) => [
                    'media' => $t->getFirstMedia('hero'),
                    'src' => null,
                    'eyebrow' => $t->destination?->name ?? 'Nepal',
                    'title' => $t->title,
                    'url' => url($t->publicPath()),
                ])->values()->all();
        }

        return $slides;
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @param  array<int, int|string>  $ids
     * @param  Collection<int, Trip>  $fallback
     * @return Collection<int, Trip>
     */
    private function collage(Collection $trips, array $ids, Collection $fallback): Collection
    {
        $picked = collect($ids)->map(fn ($id) => $trips->firstWhere('id', (int) $id))->filter(fn (?Trip $t) => $t?->hasMedia('hero'));

        return ($picked->isNotEmpty() ? $picked : $fallback)->take(3)->values();
    }

    /** @param  Collection<int, Trip>  $trips */
    private function largestHero(Collection $trips): ?Media
    {
        return $trips->map(fn (Trip $t) => $t->getFirstMedia('hero'))
            ->filter()
            ->reject(fn (Media $m) => str_contains($m->file_name, 'img-0844'))
            ->sortByDesc(fn (Media $m) => (int) $m->size)
            ->first();
    }

    /**
     * Each mood panel uses the largest hero photo among that activity's trips.
     *
     * @return array<int, array{title: string, line: string, url: string, count: int, media: ?Media}>
     */
    private function moods(array $custom = []): array
    {
        $rows = collect($custom)->filter(fn ($r) => ! empty($r['activity_id']));
        $activities = Activity::query()
            ->when($rows->isNotEmpty(), fn ($q) => $q->whereIn('id', $rows->pluck('activity_id')), fn ($q) => $q->whereIn('slug', array_keys(self::MOODS)))
            ->with(['trips' => fn ($q) => $q->published()->with('media')])
            ->get();

        $config = $rows->isNotEmpty()
            ? $rows->mapWithKeys(fn ($r) => [$activities->firstWhere('id', (int) $r['activity_id'])?->slug ?? 'missing-'.$r['activity_id'] => [
                'title' => $r['title'] ?? '', 'line' => $r['line'] ?? '', 'image' => $r['image'] ?? null,
            ]])->all()
            : self::MOODS;
        $activities = $activities->keyBy('slug');

        $panels = [];

        foreach ($config as $slug => $copy) {
            $activity = $activities->get($slug);
            if (! $activity || $activity->trips->isEmpty()) {
                continue;
            }

            $src = Content::image($copy['image'] ?? null);
            $media = $src ? null : $this->largestHero($activity->trips);

            if (! $media && ! $src) {
                continue;
            }

            $panels[] = [
                'title' => ($copy['title'] ?? '') ?: $activity->name,
                'line' => $copy['line'] ?? '',
                'src' => $src,
                'url' => url($activity->publicPath()),
                'count' => $activity->trips->count(),
                'media' => $media,
            ];
        }

        return $panels;
    }
}
