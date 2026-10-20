<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Trip;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class ActivityController extends Controller
{
    public function show(string $slug): View
    {
        $activity = Activity::where('slug', $slug)->with('media')->firstOrFail();

        $trips = $activity->trips()->published()->with(['destination', 'difficulty', 'media'])
            ->orderByDesc('is_featured')->orderBy('sort')->orderBy('duration_days')->get();

        $heroImage = $activity->getFirstMedia('hero') ?? $trips->first(fn (Trip $t) => $t->hasMedia('hero'))?->getFirstMedia('hero');

        return view('site.activities.show', [
            'activity' => $activity,
            'trips' => $trips,
            'heroMedia' => $heroImage,
            'regions' => $trips->pluck('destination')->filter()->unique('id')->values(),
            'seo' => Seo::for($activity, $activity->name.' in Nepal & the Himalaya', strip_tags((string) $activity->description) ?: "{$activity->name} journeys with Wonder Himalaya — expert local guides, small groups and tailor-made itineraries.", $heroImage?->getUrl()),
        ]);
    }
}
