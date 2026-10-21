<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Trip;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class DestinationController extends Controller
{
    public function show(string $slug): View
    {
        // Supports WordPress-style nested paths (/destination/nepal/everest-region) by using the last segment.
        $destination = Destination::where('slug', basename($slug))->with(['parent', 'children', 'media'])->firstOrFail();

        $ids = $destination->children->pluck('id')->push($destination->id);

        $trips = Trip::published()
            ->whereHas('destinations', fn ($q) => $q->whereIn('destinations.id', $ids))
            ->with(['destination', 'difficulty', 'media'])
            ->orderByDesc('is_featured')->orderBy('sort')->orderBy('duration_days')
            ->get();

        $children = $destination->children()->withCount(['trips' => fn ($q) => $q->published()])->with('media')->get()->where('trips_count', '>', 0);

        $heroImage = $destination->getFirstMedia('hero') ?? $trips->first(fn (Trip $t) => $t->hasMedia('hero'))?->getFirstMedia('hero');

        $seo = Seo::for($destination, $destination->name.' treks & tours', $destination->intro ?: strip_tags((string) $destination->description) ?: "Guided treks, tours and expeditions in {$destination->name} with Wonder Himalaya.", $heroImage?->getUrl());
        $seo->canonical ??= url($destination->publicPath());

        return view('site.destinations.show', [
            'destination' => $destination,
            'trips' => $trips,
            'children' => $children,
            'heroMedia' => $heroImage,
            'seo' => $seo,
        ]);
    }
}
