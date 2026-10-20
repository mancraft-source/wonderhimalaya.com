<?php

namespace App\View\Composers;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/** Shares navigation and company details with the public layout. */
class SiteComposer
{
    public function compose(View $view): void
    {
        $nav = Cache::remember('site.nav', now()->addMinutes(30), fn () => [
            'destinations' => Destination::query()
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->withCount('trips')->orderBy('sort')->orderBy('name')])
                ->withCount('trips')
                ->orderBy('sort')
                ->get()
                ->filter(fn (Destination $d) => $d->trips_count > 0 || $d->children->sum('trips_count') > 0)
                ->map(fn (Destination $d) => [
                    'name' => $d->name,
                    'url' => url($d->publicPath()),
                    'children' => $d->children->where('trips_count', '>', 0)->map(fn (Destination $c) => [
                        'name' => $c->name, 'url' => url($c->publicPath()), 'count' => $c->trips_count,
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
