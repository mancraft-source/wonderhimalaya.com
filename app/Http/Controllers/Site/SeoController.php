<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Trip;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $map = Sitemap::create();

            foreach (['/', '/trips', '/fixed-departures', '/tailor-made', '/b2b', '/contact', '/our-team', '/blog'] as $path) {
                $map->add(Url::create(url($path))->setPriority($path === '/' ? 1.0 : 0.7));
            }

            Trip::published()->with('media')->each(function (Trip $trip) use ($map) {
                $url = Url::create($trip->url())->setLastModificationDate($trip->updated_at)->setPriority(0.9);
                if ($img = $trip->getFirstMediaUrl('hero')) {
                    $url->addImage($img, $trip->title);
                }
                $map->add($url);
            });

            Destination::each(fn ($d) => $map->add(Url::create(url($d->publicPath()))->setLastModificationDate($d->updated_at)->setPriority(0.8)));
            Activity::each(fn ($a) => $map->add(Url::create(url($a->publicPath()))->setLastModificationDate($a->updated_at)->setPriority(0.7)));
            Post::published()->each(fn ($p) => $map->add(Url::create(url($p->publicPath()))->setLastModificationDate($p->updated_at)->setPriority(0.6)));
            Page::published()->each(fn ($p) => $p->seoNoindex() ? null : $map->add(Url::create(url($p->publicPath()))->setLastModificationDate($p->updated_at)->setPriority(0.5)));
            TeamMember::where('is_published', true)->each(fn ($m) => $map->add(Url::create(url($m->publicPath()))->setPriority(0.4)));

            return $map->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /livewire', 'Disallow: /quote/', 'Disallow: /pay/', 'Disallow: /booking/', 'Disallow: /*?*sort=', '', 'Sitemap: '.url('/sitemap.xml')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
