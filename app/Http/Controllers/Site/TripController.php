<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\FindsPublished;
use App\Models\Setting;
use App\Models\Trip;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use FindsPublished;

    public function index(Request $request): View
    {
        return view('site.trips.index', [
            'seo' => Seo::make('All treks, tours & expeditions', 'Browse every Wonder Himalaya journey — guided treks, peak climbs, heli tours, safaris and cultural tours across Nepal, Tibet and Bhutan. Filter by region, style, duration and difficulty.'),
        ]);
    }

    public function show(string $slug): View
    {
        $trip = $this->scopeVisible(Trip::query())
            ->where('slug', $slug)
            ->with(['destination.parent', 'destinations', 'activities', 'difficulty', 'tiers', 'days', 'faqs', 'prices.tier', 'upcomingDepartures.tier', 'media', 'testimonials' => fn ($q) => $q->where('is_published', true)])
            ->firstOrFail();

        $related = $trip->related()->published()->with(['destination', 'difficulty', 'media'])->limit(3)->get();
        if ($related->count() < 3 && $trip->destination_id) {
            $related = $related->merge(
                Trip::published()->where('destination_id', $trip->destination_id)->whereKeyNot($trip->id)->whereNotIn('id', $related->pluck('id'))
                    ->with(['destination', 'difficulty', 'media'])->inRandomOrder()->limit(3 - $related->count())->get()
            );
        }

        $hero = $trip->getFirstMedia('hero');
        $seo = Seo::for($trip, $trip->title, $trip->excerpt ?: strip_tags((string) $trip->overview), $hero?->getUrl())->type('product');
        $seo->withSchema($this->tripSchema($trip, $hero?->getUrl()));
        if ($trip->faqs->isNotEmpty()) {
            $seo->withSchema([
                '@type' => 'FAQPage',
                'mainEntity' => $trip->faqs->map(fn ($f) => [
                    '@type' => 'Question', 'name' => $f->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f->answer)],
                ])->all(),
            ]);
        }

        return view('site.trips.show', [
            'trip' => $trip,
            'related' => $related,
            'seo' => $seo,
            'altitudes' => $trip->days->filter(fn ($d) => $d->altitude_m)->map(fn ($d) => ['day' => $d->day_number, 'alt' => $d->altitude_m, 'title' => $d->title])->values(),
            'booking' => Setting::get('booking', []),
        ]);
    }

    /** @return array<string, mixed> */
    private function tripSchema(Trip $trip, ?string $image): array
    {
        $schema = [
            '@type' => ['TouristTrip', 'Product'],
            'name' => $trip->title,
            'description' => str(strip_tags($trip->excerpt ?: (string) $trip->overview))->squish()->limit(300)->toString(),
            'url' => $trip->url(),
            'image' => $image,
            'sku' => $trip->trip_code,
            'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
            'provider' => ['@id' => url('/').'#organization'],
            'touristType' => $trip->activities->pluck('name')->all(),
            'itinerary' => [
                '@type' => 'ItemList',
                'numberOfItems' => $trip->days->count(),
                'itemListElement' => $trip->days->values()->map(fn ($d, $i) => [
                    '@type' => 'ListItem', 'position' => $i + 1, 'name' => 'Day '.$d->day_number.': '.strip_tags($d->title),
                ])->all(),
            ],
        ];

        if ($price = $trip->displayPrice()) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => number_format($price, 2, '.', ''),
                'priceCurrency' => $trip->currency ?: 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => $trip->url(),
                'validFrom' => now()->startOfYear()->toDateString(),
            ];
        }

        return array_filter($schema);
    }
}
