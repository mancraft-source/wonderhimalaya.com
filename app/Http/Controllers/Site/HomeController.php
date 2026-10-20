<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $trips = Trip::published()->with(['destination', 'difficulty', 'media'])->orderBy('sort')->latest('published_at')->get();

        $signature = $trips->where('is_featured', true)->take(4);
        if ($signature->count() < 4) {
            $signature = $signature->merge($trips->whereNotIn('id', $signature->pluck('id'))->take(4 - $signature->count()));
        }

        $regions = Destination::query()
            ->whereHas('parent', fn ($q) => $q->where('slug', 'nepal'))
            ->withCount(['trips' => fn ($q) => $q->published()])
            ->with(['media', 'trips' => fn ($q) => $q->published()->with('media')->orderByDesc('is_featured')->limit(4)])
            ->orderBy('sort')
            ->get()
            ->where('trips_count', '>', 0)
            ->sortByDesc('trips_count')
            ->values();

        $countries = Destination::whereNull('parent_id')->with('media')->withCount(['trips' => fn ($q) => $q->published()])->orderBy('sort')->get();

        $departures = TripDeparture::query()
            ->with(['trip.destination', 'trip.media'])
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
            'signature' => $signature,
            'tripOfMonth' => $trips->firstWhere('is_trip_of_month', true),
            'topSeller' => $trips->firstWhere('is_top_selling', true),
            'popular' => $trips->where('is_popular', true)->take(6)->values(),
            'fixedTrips' => $trips->where('is_fixed_departure', true)->take(6)->values(),
            'departures' => $departures,
            'regions' => $regions,
            'countries' => $countries,
            'heroTrips' => $signature->filter(fn (Trip $t) => $t->hasMedia('hero'))->take(3)->values(),
            'testimonials' => Testimonial::where('is_published', true)->orderByDesc('is_featured')->latest('reviewed_at')->limit(8)->get(),
            'posts' => Post::published()->with(['media', 'category'])->latest('published_at')->limit(3)->get(),
            'stats' => Setting::get('stats', []),
            'reviews' => Setting::get('reviews', []),
        ]);
    }
}
