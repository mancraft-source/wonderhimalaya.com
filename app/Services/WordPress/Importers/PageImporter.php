<?php

namespace App\Services\WordPress\Importers;

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\TitleCleaner;
use App\Services\WordPress\WordPressSource;
use Illuminate\Support\Str;

class PageImporter extends Importer
{
    public function import(): ImportStats
    {
        // Parents first so child paths (travel-guide/…) can be built.
        $pages = collect($this->source->pages())
            ->filter(fn ($p) => $this->source->pagePath($p) !== null)
            ->sortBy(fn ($p) => [substr_count((string) $this->source->pagePath($p), '/'), $p['menu_order'] ?? 0, $p['id']]);

        foreach ($this->source->pages() as $page) {
            if ($this->source->pagePath($page) === null) {
                $this->stats->skip('pages');
            }
        }

        foreach ($pages as $wp) {
            $this->log('Page '.$wp['id'].': '.$wp['slug']);
            $this->importPage($wp);
        }

        return $this->stats;
    }

    public function importPage(array $wp): Page
    {
        $path = (string) $this->source->pagePath($wp);
        $segments = explode('/', $path);
        $slug = array_pop($segments);
        $parentPath = implode('/', $segments);
        $parent = $parentPath !== '' ? $this->ensureParent($parentPath) : null;
        $title = TitleCleaner::text($wp['title']['rendered'] ?? $slug);

        $page = $this->locate(Page::class, (int) $wp['id']);

        if (! $page->exists) {
            $page = Page::withTrashed()->where('path', $path)->whereNull('wp_id')->first() ?? $page;
        }

        $content = $this->cleaner()->clean($wp['content']['rendered'] ?? '', $title);
        $template = $this->source->pageTemplate($wp);
        $seo = WordPressSource::seo($wp);

        $page->fill([
            'title' => $title,
            'slug' => $slug,
            'parent_id' => $parent?->id,
            'template' => $template,
            'excerpt' => $seo['description'] ?: (HtmlCleaner::excerpt($content, 40) ?: null),
            'blocks' => $content !== '' ? [['type' => 'rich_text', 'data' => ['content' => $content]]] : [],
            'status' => PublishStatus::Published,
            'published_at' => $this->date($wp),
            'show_in_footer' => $template === 'legal',
            'sort' => (int) ($wp['menu_order'] ?? 0),
            'seo' => $seo,
            'wp_id' => (int) $wp['id'],
        ])->save();

        $this->stats->record('pages', $page);

        $this->attachHero($page, $wp);

        return $page;
    }

    protected function attachHero(Page $page, array $wp): void
    {
        if ($this->media->enabled() && $hero = $this->source->mediaItem((int) ($wp['featured_media'] ?? 0))) {
            $this->media->attach($page, 'hero', $hero['url'], $hero['alt'] ?: $page->title, $hero['id']);
        }
    }

    /** Attach the hero and mirror inline images for an already-imported page. */
    public function importMedia(Page $page): void
    {
        if ($wp = $this->source->pages()[$page->wp_id] ?? null) {
            $this->attachHero($page, $wp);
        }

        $cleaner = $this->cleaner();
        $blocks = collect($page->blocks ?? [])->map(function ($block) use ($cleaner) {
            if (isset($block['data']['content']) && is_string($block['data']['content'])) {
                $block['data']['content'] = $cleaner->rewriteUrls($block['data']['content']);
            }

            return $block;
        })->all();

        $page->update(['blocks' => $blocks]);
        $this->stats->add('pages (media)', 'updated');
    }

    /** Parent pages are themselves WP pages (e.g. travel-guide); create a stub if missing. */
    protected function ensureParent(string $path): Page
    {
        $existing = Page::withTrashed()->where('path', $path)->first();

        if ($existing) {
            return $existing;
        }

        $wp = collect($this->source->pages())->first(fn ($p) => $this->source->pagePath($p) === $path);

        if ($wp) {
            return $this->importPage($wp);
        }

        return Page::create([
            'title' => Str::headline(basename($path)),
            'slug' => basename($path),
            'status' => PublishStatus::Published,
            'template' => 'default',
            'blocks' => [],
        ]);
    }
}
