<?php

namespace App\Services\WordPress\Importers;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Difficulty;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\Tier;
use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\TitleCleaner;
use App\Services\WordPress\WordPressSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

class TaxonomyImporter extends Importer
{
    public const DIFFICULTY_LEVELS = ['easy' => 1, 'medium' => 2, 'moderate' => 2, 'hard' => 3, 'difficult' => 3, 'extreme' => 4, 'strenuous' => 4];

    public const TIER_SORT = ['standard' => 1, 'premium' => 2, 'luxury' => 3];

    public function import(): ImportStats
    {
        $this->destinations();
        $this->activities();
        $this->difficulties();
        $this->tiers();
        $this->tags();
        $this->categories();

        return $this->stats;
    }

    protected function destinations(): void
    {
        $terms = collect($this->source->terms('destination'))
            ->sortBy(fn ($t) => [$t['parent'] ? 1 : 0, $t['name']]);
        $sort = 0;

        foreach ($terms as $term) {
            $model = $this->locate(Destination::class, $term['id'], 'slug', $term['slug']);
            $parent = $term['parent'] ? Destination::where('wp_id', $term['parent'])->value('id') : null;
            $description = $this->cleaner()->clean($term['description'] ?? '');

            $model->fill([
                'name' => TitleCleaner::text($term['name']),
                'slug' => $this->uniqueSlug(Destination::class, $term['slug'], $model->id),
                'parent_id' => $parent,
                'intro' => $this->intro($description),
                'description' => $description ?: null,
                'sort' => $model->exists ? $model->sort : ++$sort,
                'seo' => WordPressSource::seo($term, $term['name'].' Archives'),
                'wp_id' => $term['id'],
            ])->save();

            $this->stats->record('destinations', $model);
            $this->termImage($model, $term);
        }
    }

    protected function activities(): void
    {
        foreach ($this->source->terms('activities') as $term) {
            $model = $this->locate(Activity::class, $term['id'], 'slug', $term['slug']);

            $model->fill([
                'name' => TitleCleaner::text($term['name']),
                'slug' => $this->uniqueSlug(Activity::class, $term['slug'], $model->id),
                'description' => $this->cleaner()->clean($term['description'] ?? '') ?: null,
                'seo' => WordPressSource::seo($term, $term['name'].' Archives'),
                'wp_id' => $term['id'],
            ])->save();

            $this->stats->record('activities', $model);
            $this->termImage($model, $term);
        }
    }

    protected function difficulties(): void
    {
        foreach ($this->source->terms('difficulty') as $term) {
            $model = $this->locate(Difficulty::class, $term['id'], 'slug', $term['slug']);

            $model->fill([
                'name' => TitleCleaner::text($term['name']),
                'slug' => $this->uniqueSlug(Difficulty::class, $term['slug'], $model->id),
                'level' => self::DIFFICULTY_LEVELS[$term['slug']] ?? 2,
                'description' => HtmlCleaner::text($term['description'] ?? '') ?: null,
                'wp_id' => $term['id'],
            ])->save();

            $this->stats->record('difficulties', $model);
        }
    }

    protected function tiers(): void
    {
        foreach ($this->source->terms('trip_types') as $term) {
            if ($term['slug'] === WordPressSource::FIX_DEPARTURE_SLUG) {
                $this->stats->skip('tiers');

                continue;
            }

            $model = $this->locate(Tier::class, $term['id'], 'slug', $term['slug']);

            $model->fill([
                'name' => TitleCleaner::text($term['name']),
                'slug' => $this->uniqueSlug(Tier::class, $term['slug'], $model->id),
                'description' => HtmlCleaner::text($term['description'] ?? '') ?: $model->description,
                'sort' => self::TIER_SORT[$term['slug']] ?? 9,
                'wp_id' => $term['id'],
            ])->save();

            $this->stats->record('tiers', $model);
        }
    }

    protected function tags(): void
    {
        $terms = array_merge(array_values($this->source->terms('trip_tag')), array_values($this->source->terms('tags')));

        foreach ($terms as $term) {
            $slug = Str::slug($term['slug']) ?: Str::slug(TitleCleaner::text($term['name']));
            $model = Tag::firstOrNew(['slug' => $slug]);

            if (! $model->exists || $model->name === null) {
                $model->name = Str::ucfirst(TitleCleaner::text($term['name']));
            }

            $model->save();
            $this->stats->record('tags', $model);
        }
    }

    protected function categories(): void
    {
        foreach ($this->source->terms('categories') as $term) {
            $model = $this->locate(PostCategory::class, $term['id'], 'slug', $term['slug']);

            $model->fill([
                'name' => TitleCleaner::text($term['name']),
                'slug' => $this->uniqueSlug(PostCategory::class, $term['slug'], $model->id),
                'description' => HtmlCleaner::text($term['description'] ?? '') ?: null,
                'wp_id' => $term['id'],
            ])->save();

            $this->stats->record('post_categories', $model);
        }
    }

    /** Term images for already-imported destinations and activities. */
    public function importMedia(): void
    {
        foreach ([[Destination::class, 'destination'], [Activity::class, 'activities']] as [$class, $taxonomy]) {
            $terms = $this->source->terms($taxonomy);

            foreach ($class::whereNotNull('wp_id')->get() as $model) {
                if (isset($terms[$model->wp_id])) {
                    $this->termImage($model, $terms[$model->wp_id]);
                }
            }
        }
    }

    /** First paragraph as plain text, capped for the `intro` column. */
    protected function intro(string $html): ?string
    {
        if (! preg_match('/<p>(.*?)<\/p>/s', $html, $m)) {
            return null;
        }

        return Str::limit(HtmlCleaner::text($m[1]), 600) ?: null;
    }

    /** Term "thumbnail" (WTE term image) → hero collection. */
    protected function termImage(HasMedia&Model $model, array $term): void
    {
        $file = $term['thumbnail']['file'] ?? null;

        if ($file && $this->media->enabled()) {
            $url = $this->source->client->baseUrl().'/wp-content/uploads/'.ltrim($file, '/');
            $this->media->attach($model, 'hero', $url, TitleCleaner::text($term['name']));
        }
    }
}
