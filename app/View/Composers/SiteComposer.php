<?php

namespace App\View\Composers;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Trip;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/** Shares navigation and company details with the public layout. */
class SiteComposer
{
    public function compose(View $view): void
    {
        $nav = Cache::remember('site.nav.v2', now()->addMinutes(30), fn () => [
            // Countries with journeys; a country's regions are all listed (the mega menu covers the whole of Nepal),
            // with region journey counts of 0 shown as "made to order".
            'destinations' => Destination::query()
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->withCount('trips')->orderBy('sort')->orderBy('name'), 'children.media', 'media'])
                ->withCount('trips')
                ->orderBy('sort')
                ->get()
                ->filter(fn (Destination $d) => $d->trips_count > 0 || $d->children->sum('trips_count') > 0)
                ->map(fn (Destination $d) => [
                    'name' => $d->name,
                    'url' => url($d->publicPath()),
                    'count' => $d->children->isEmpty() ? $d->trips_count : Trip::query()
                        ->whereHas('destinations', fn ($q) => $q->whereIn('destinations.id', $d->children->pluck('id')->push($d->id)))
                        ->count(),
                    'image' => $d->imageUrl('hero', 'thumb'),
                    'tagline' => $d->tagline,
                    'children' => $d->children->map(fn (Destination $c) => [
                        'name' => $c->name, 'url' => url($c->publicPath()), 'count' => $c->trips_count, 'image' => $c->imageUrl('hero', 'thumb'),
                    ])->values()->all(),
                ])->values()->all(),
            'activities' => Activity::query()->withCount('trips')->orderBy('sort')->orderBy('name')->get()
                ->where('trips_count', '>', 0)
                ->map(fn (Activity $a) => ['name' => $a->name, 'url' => url($a->publicPath()), 'count' => $a->trips_count])
                ->values()->all(),
            'guides' => Page::published()->where('path', 'like', 'travel-guide/%')->orderBy('sort')->orderBy('title')->get(['title', 'path', 'slug'])
                ->map(fn (Page $p) => ['name' => $p->title, 'url' => url($p->publicPath())])->all(),
            'legal' => Page::published()->where('show_in_footer', true)->orderBy('sort')->orderBy('title')->get(['title', 'path', 'slug'])
                ->map(fn (Page $p) => ['name' => $p->title, 'url' => url($p->publicPath())])->all(),
        ]);

        $view->with([
            'nav' => $nav,
            'headerMenu' => Menu::itemsFor('header'),
            'company' => Setting::get('company', []),
            'social' => array_filter(Setting::get('social', [])),
            'analytics' => Setting::get('analytics', []),
        ]);
    }
}
