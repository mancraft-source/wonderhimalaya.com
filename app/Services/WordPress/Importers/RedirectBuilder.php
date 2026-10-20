<?php

namespace App\Services\WordPress\Importers;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\TeamMember;
use App\Models\Trip;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\UrlMap;
use Illuminate\Database\Eloquent\Model;

/** Builds 301s from every known old WordPress URL to its new home. */
class RedirectBuilder extends Importer
{
    public function import(): ImportStats
    {
        $this->content();
        $this->archives();

        foreach (UrlMap::SYSTEM_PAGES as $from => $to) {
            $this->point($from, $to, 'system');
        }

        return $this->stats;
    }

    /** @return array<int, array{old: string, new: string, type: string, title: string}> */
    public function rows(): array
    {
        $rows = [];
        $add = function (string $type, string $old, Model $model, string $title) use (&$rows) {
            $rows[] = ['old' => $old, 'new' => $model->publicPath(), 'type' => $type, 'title' => $title];
        };

        $pages = $this->source->pages();
        $posts = $this->source->posts();

        foreach (Trip::withTrashed()->whereNotNull('wp_slug')->orderBy('id')->get() as $trip) {
            $add('trip', '/trip/'.$trip->wp_slug, $trip, $trip->title);
        }

        foreach (Post::withTrashed()->whereNotNull('wp_id')->orderBy('id')->get() as $post) {
            $old = isset($posts[$post->wp_id]) ? $this->source->oldPath($posts[$post->wp_id]) : '/'.$post->wp_slug;
            $add('post', $old, $post, $post->title);
        }

        foreach (Page::withTrashed()->whereNotNull('wp_id')->orderBy('id')->get() as $page) {
            if (isset($pages[$page->wp_id])) {
                $add('page', $this->source->oldPath($pages[$page->wp_id]), $page, $page->title);
            }
        }

        foreach (TeamMember::whereNotNull('wp_id')->orderBy('sort')->get() as $member) {
            if (isset($pages[$member->wp_id])) {
                $add('team', $this->source->oldPath($pages[$member->wp_id]), $member, $member->name);
            }
        }

        $destinations = $this->source->terms('destination');

        foreach (Destination::whereNotNull('wp_id')->orderBy('id')->get() as $destination) {
            if (isset($destinations[$destination->wp_id])) {
                $add('destination', $this->source->oldPath($destinations[$destination->wp_id]), $destination, $destination->name);
            }
        }

        $activities = $this->source->terms('activities');

        foreach (Activity::whereNotNull('wp_id')->orderBy('id')->get() as $activity) {
            if (isset($activities[$activity->wp_id])) {
                $add('activity', $this->source->oldPath($activities[$activity->wp_id]), $activity, $activity->name);
            }
        }

        return $rows;
    }

    protected function content(): void
    {
        foreach ($this->rows() as $row) {
            $this->point($row['old'], $row['new'], $row['type']);
        }
    }

    protected function archives(): void
    {
        $this->point('/trip', '/trips', 'archive');

        foreach ($this->source->terms('trip_types') as $term) {
            $target = UrlMap::pattern('/trip-types/'.$term['slug']);
            $this->point($this->source->oldPath($term), $target, 'archive');
            $this->point('/trip-types/'.$term['slug'], $target, 'archive');
        }

        foreach ($this->source->terms('difficulty') as $term) {
            $target = '/trips?difficulty='.$term['slug'];
            $this->point($this->source->oldPath($term), $target, 'archive');
            $this->point('/difficulty/'.$term['slug'], $target, 'archive');
        }

        foreach ($this->source->terms('trip_tag') as $term) {
            $this->point($this->source->oldPath($term), '/trips', 'archive');
            $this->point('/trip_tag/'.$term['slug'], '/trips', 'archive');
        }

        foreach ($this->source->terms('categories') as $term) {
            $target = $term['slug'] === 'uncategorized' ? '/blog' : '/blog?category='.$term['slug'];
            $this->point($this->source->oldPath($term), $target, 'archive');
            $this->point('/category/'.$term['slug'], $target, 'archive');
        }

        foreach ($this->source->terms('tags') as $term) {
            $this->point($this->source->oldPath($term), '/blog', 'archive');
        }

        foreach ($this->source->users() as $user) {
            $this->point('/author/'.$user['slug'], '/about-us', 'archive');
        }

        $pages = max(2, (int) ceil(count($this->source->posts()) / 9) + 1);

        for ($n = 2; $n <= $pages; $n++) {
            $this->point('/blog/page/'.$n, '/blog', 'archive');
        }
    }

    /**
     * Redirect::point() normalises the target path and would drop a query
     * string, so query targets (e.g. /trips?tier=premium) are stored directly.
     */
    public function point(string $from, ?string $to, string $type = 'other'): void
    {
        if ($to === null) {
            return;
        }

        $fromPath = Redirect::normalize($from);
        $toPath = str_contains($to, '?') ? Redirect::normalize($to).'?'.parse_url($to, PHP_URL_QUERY) : Redirect::normalize($to);

        if ($fromPath === $toPath || $fromPath === '/') {
            return;
        }

        $before = Redirect::where('from_path', $fromPath)->first();

        if (str_contains($toPath, '?')) {
            Redirect::where('to_path', $fromPath)->update(['to_path' => $toPath]);
            Redirect::updateOrCreate(['from_path' => $fromPath], ['to_path' => $toPath, 'status_code' => 301, 'is_active' => true]);
        } else {
            Redirect::point($fromPath, $toPath);
        }

        $this->stats->add('redirects ('.$type.')', match (true) {
            $before === null => 'created',
            $before->to_path !== $toPath => 'updated',
            default => 'unchanged',
        });
    }
}
