<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Trip;
use App\Support\Seo\SeoSettings;
use Illuminate\Database\Eloquent\Model;
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

            Trip::published()->with(['media', 'destination', 'difficulty'])->each(function (Trip $trip) use ($map) {
                if (! $trip->seoInSitemap()) {
                    return;
                }
                $url = Url::create($trip->url())->setLastModificationDate($trip->updated_at)->setPriority(0.9);
                if ($img = $trip->getFirstMediaUrl('hero')) {
                    $url->addImage($img, $trip->title);
                }
                $map->add($url);
            });

            $add = function (Model $model, float $priority) use ($map): void {
                if ($model->seoInSitemap()) {
                    $map->add(Url::create(url($model->publicPath()))->setLastModificationDate($model->updated_at)->setPriority($priority));
                }
            };

            Destination::with('parent')->each(fn (Destination $d) => $add($d, 0.8));
            Activity::query()->each(fn (Activity $a) => $add($a, 0.7));
            Post::published()->with(['category', 'author'])->each(fn (Post $p) => $add($p, 0.6));
            Page::published()->with('parent')->each(fn (Page $p) => $add($p, 0.5));
            TeamMember::where('is_published', true)->each(fn (TeamMember $m) => $add($m, 0.4));

            return $map->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = SeoSettings::indexable()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /livewire', 'Disallow: /quote/', 'Disallow: /pay/', 'Disallow: /booking/', 'Disallow: /search', 'Disallow: /*?*sort=']
            : ['User-agent: *', 'Disallow: /'];

        if (SeoSettings::indexable() && filled($extra = SeoSettings::get('robots_extra'))) {
            $lines = [...$lines, '', ...preg_split('/\r\n|\r|\n/', trim($extra))];
        }

        $lines = [...$lines, '', 'Sitemap: '.url('/sitemap.xml')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
