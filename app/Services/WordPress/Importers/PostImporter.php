<?php

namespace App\Services\WordPress\Importers;

use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\TitleCleaner;
use App\Services\WordPress\WordPressSource;
use Illuminate\Support\Str;

class PostImporter extends Importer
{
    public function import(): ImportStats
    {
        $posts = collect($this->source->posts())
            ->filter(fn ($p) => ($p['status'] ?? 'publish') === 'publish')
            ->sortBy(fn ($p) => [$p['date_gmt'] ?? $p['date'], $p['id']]);

        foreach ($posts as $wp) {
            $this->log('Post '.$wp['id'].': '.$wp['slug']);
            $this->importPost($wp);
        }

        return $this->stats;
    }

    public static function cleanTitle(string $title): string
    {
        $title = TitleCleaner::text($title);

        // "The Silent Roar of Kanchenjunga." / "Monasteries at the Edge of the Sky ."
        return trim((string) preg_replace('/\s*\.(?!\.)\s*$/u', '', $title));
    }

    public function importPost(array $wp): Post
    {
        $id = (int) $wp['id'];
        $title = self::cleanTitle($wp['title']['rendered'] ?? '');
        $post = $this->locate(Post::class, $id);
        $body = $this->cleaner()->clean($wp['content']['rendered'] ?? '', $title);
        $excerpt = HtmlCleaner::text(preg_replace('/\[(&hellip;|…|\.\.\.)\]/u', '', (string) ($wp['excerpt']['rendered'] ?? '')));

        $post->fill([
            'post_category_id' => $this->categoryId($wp),
            'title' => Str::limit($title, 250, ''),
            'slug' => $this->uniqueSlug(Post::class, $this->source->postSlugs()[$id] ?? $wp['slug'], $post->id),
            'excerpt' => Str::words($excerpt ?: HtmlCleaner::text($body), 40, '…') ?: null,
            'body' => $body ?: null,
            'is_featured' => (bool) ($wp['sticky'] ?? false),
            'status' => PublishStatus::Published,
            'published_at' => $this->date($wp),
            'seo' => WordPressSource::seo($wp),
            'wp_id' => $id,
            'wp_slug' => $wp['slug'],
        ])->save();

        $this->stats->record('posts', $post);

        $tagSlugs = $this->source->termSlugs($wp, 'tags');
        $post->tags()->sync(Tag::whereIn('slug', $tagSlugs)->pluck('id'));

        $this->attachHero($post, $wp);

        return $post;
    }

    protected function attachHero(Post $post, array $wp): void
    {
        if ($this->media->enabled() && $hero = $this->source->mediaItem((int) ($wp['featured_media'] ?? 0))) {
            $this->media->attach($post, 'hero', $hero['url'], $hero['alt'] ?: $post->title, $hero['id']);
        }
    }

    /** Attach the hero and mirror inline images for an already-imported post. */
    public function importMedia(Post $post): void
    {
        if ($wp = $this->source->posts()[$post->wp_id] ?? null) {
            $this->attachHero($post, $wp);
        }

        $post->update(['body' => $this->cleaner()->rewriteUrls($post->body) ?: null]);
        $this->stats->add('posts (media)', 'updated');
    }

    /** The first real category (skipping "Uncategorized"). */
    protected function categoryId(array $wp): ?int
    {
        $terms = $this->source->terms('categories');
        $ids = collect($wp['categories'] ?? [])->reject(fn ($id) => ($terms[$id]['slug'] ?? '') === 'uncategorized');
        $wpId = $ids->first() ?? collect($wp['categories'] ?? [])->first();

        return $wpId ? PostCategory::where('wp_id', $wpId)->value('id') : null;
    }
}
