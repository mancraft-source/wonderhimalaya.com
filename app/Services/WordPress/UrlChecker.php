<?php

namespace App\Services\WordPress;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\TeamMember;
use App\Models\Trip;
use Illuminate\Http\Request;

/**
 * Resolves old-site URLs (from the Yoast sitemaps) against the new site:
 * either directly (content exists), through a redirect, or missing.
 */
class UrlChecker
{
    /** Routes the new site provides in code (not backed by a content record). */
    public const STATIC_PATHS = ['/', '/trips', '/blog', '/contact', '/fixed-departures', '/destinations', '/activities', '/our-team', '/about-us', '/search'];

    public function __construct(protected WordPressClient $client) {}

    /** @return array<int, string> every <loc> from the sitemap index and its child sitemaps */
    public function sitemapUrls(string $index = '/sitemap_index.xml'): array
    {
        $urls = [];

        foreach ($this->locs($this->fetch($index)) as $sitemap) {
            if (str_ends_with(parse_url($sitemap, PHP_URL_PATH) ?: '', '.xml')) {
                array_push($urls, ...$this->locs($this->fetch($sitemap)));
            } else {
                $urls[] = $sitemap;
            }
        }

        return array_values(array_unique($urls));
    }

    protected function fetch(string $url): string
    {
        try {
            return $this->client->html($url);
        } catch (\Throwable) {
            return '';
        }
    }

    /** @return array<int, string> */
    protected function locs(string $xml): array
    {
        preg_match_all('#<loc>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</loc>#i', $xml, $m);

        return array_map(fn ($u) => html_entity_decode(trim($u)), $m[1]);
    }

    /**
     * @return array{status: string, target: ?string}
     *                                                status: ok | redirected | redirect-broken | missing
     */
    public function check(string $url, bool $http = false): array
    {
        $path = Redirect::normalize($url);

        if ($http) {
            return $this->checkHttp($path);
        }

        if ($this->exists($path)) {
            return ['status' => 'ok', 'target' => $path];
        }

        $target = $path;

        for ($hops = 0; $hops < 5; $hops++) {
            $redirect = Redirect::where('from_path', Redirect::normalize($target))->where('is_active', true)->first();

            if (! $redirect) {
                break;
            }

            $target = $redirect->to_path;

            if ($this->exists($target)) {
                return ['status' => 'redirected', 'target' => $target];
            }
        }

        return $target !== $path
            ? ['status' => 'redirect-broken', 'target' => $target]
            : ['status' => 'missing', 'target' => null];
    }

    /** Is there a route/record that answers this new path? */
    public function exists(string $target): bool
    {
        if (str_starts_with($target, 'http')) {
            return true;
        }

        $path = Redirect::normalize($target);

        if (in_array($path, self::STATIC_PATHS, true)) {
            return true;
        }

        $segments = explode('/', trim($path, '/'));
        $slug = end($segments);

        return match (true) {
            $segments[0] === 'trip' && count($segments) === 2 => Trip::published()->where('slug', $slug)->exists(),
            $segments[0] === 'blog' && count($segments) === 2 => Post::published()->where('slug', $slug)->exists(),
            $segments[0] === 'destination' && count($segments) === 2 => Destination::where('slug', $slug)->exists(),
            $segments[0] === 'activities' && count($segments) === 2 => Activity::where('slug', $slug)->exists(),
            $segments[0] === 'our-team' && count($segments) === 2 => TeamMember::where('slug', $slug)->where('is_published', true)->exists(),
            default => Page::published()->where('path', trim($path, '/'))->exists(),
        };
    }

    /** @return array{status: string, target: ?string} */
    protected function checkHttp(string $path): array
    {
        $response = app()->handle(Request::create($path, 'GET'));
        $status = $response->getStatusCode();

        if ($status >= 300 && $status < 400) {
            return ['status' => 'redirected', 'target' => $response->headers->get('Location')];
        }

        return ['status' => $status < 300 ? 'ok' : 'missing', 'target' => $path.' ['.$status.']'];
    }
}
