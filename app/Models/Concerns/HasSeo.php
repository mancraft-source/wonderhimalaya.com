<?php

namespace App\Models\Concerns;

use App\Support\Seo\SeoAnalyzer;
use App\Support\Seo\SeoSettings;
use App\Support\Seo\SeoTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Per-record SEO, stored in the `seo` JSON column:
 * title, description (both may use %%variables%%), focus_keyphrase, canonical, noindex, nofollow,
 * exclude_from_sitemap, og_title, og_description, og_image, and a computed score.
 *
 * Blank title/description fall back to the content type's template under SEO → SEO settings.
 */
trait HasSeo
{
    public static function bootHasSeo(): void
    {
        static::saving(function (self $model): void {
            $seo = (array) ($model->seo ?? []);
            $seo['score'] = $model->seoAnalysis()['score'];
            $model->seo = $seo;
        });
        static::saved(fn () => Cache::forget('sitemap.xml'));
        static::deleted(fn () => Cache::forget('sitemap.xml'));
    }

    public function initializeHasSeo(): void
    {
        $this->mergeCasts(['seo' => 'array']);
    }

    /** Key of this content type in SeoSettings::TYPES. */
    public function seoType(): string
    {
        return Str::snake(class_basename($this));
    }

    /**
     * Values for %%variables%% in title and description templates.
     *
     * @return array<string, string|int|float|null>
     */
    public function seoVariables(): array
    {
        return [
            'title' => $this->title ?? $this->name ?? '',
            'excerpt' => $this->seoExcerpt(),
            'tagline' => $this->tagline ?? null,
            ...$this->seoExtraVariables(),
        ];
    }

    /** @return array<string, string|int|float|null> */
    protected function seoExtraVariables(): array
    {
        return [];
    }

    /** A related model's attribute without tripping strict-mode lazy-loading protection. */
    protected function seoRelated(string $relation, string $column = 'name'): ?string
    {
        if ($this->relationLoaded($relation)) {
            return $this->getRelation($relation)?->{$column};
        }

        return $this->{$relation}()->value($column);
    }

    /** Main body HTML, used for the excerpt fallback and the content checks. */
    public function seoContentHtml(): string
    {
        foreach (['overview', 'body', 'description', 'bio', 'content'] as $field) {
            if (filled($this->{$field} ?? null)) {
                return (string) $this->{$field};
            }
        }

        return '';
    }

    public function seoExcerpt(): string
    {
        $source = $this->excerpt ?? $this->intro ?? null;
        $source = filled($source) ? $source : $this->seoContentHtml();

        return Str::of(html_entity_decode(strip_tags(str_replace('<', ' <', (string) $source)), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->squish()->limit(155, '…')->toString();
    }

    /**
     * Final <title>: the record's own title (variables allowed) or the type template. A custom title without
     * variables gets the brand suffix when it fits; templates drop the brand when the result would exceed SeoSettings::MAX_TITLE_LENGTH.
     */
    public function seoTitle(): string
    {
        $variables = $this->seoVariables();
        $custom = trim((string) ($this->seo['title'] ?? ''));
        $template = $custom !== '' ? $custom : SeoSettings::type($this->seoType())['title'];

        if ($custom !== '' && ! str_contains($custom, '%%') && ! str_contains($custom, SeoSettings::siteName())) {
            $template .= ' %%sep%% %%sitename%%';
        }

        $title = SeoTemplate::render($template, $variables);

        if (mb_strlen($title) > SeoSettings::MAX_TITLE_LENGTH) {
            $short = SeoTemplate::render(preg_replace('/\s*%%sep%%\s*%%sitename%%\s*$/', '', $template), $variables);
            $title = $short !== '' ? $short : $title;
        }

        return $title !== '' ? $title : (string) ($variables['title'] ?: SeoSettings::siteName());
    }

    public function seoDescription(): ?string
    {
        $custom = trim((string) ($this->seo['description'] ?? ''));
        $description = SeoTemplate::render($custom !== '' ? $custom : SeoSettings::type($this->seoType())['description'], $this->seoVariables());

        return $description !== '' ? Str::limit($description, 160, '…') : null;
    }

    public function seoNoindex(): bool
    {
        return (bool) ($this->seo['noindex'] ?? false) || (bool) (SeoSettings::type($this->seoType())['noindex'] ?? false);
    }

    public function seoNofollow(): bool
    {
        return (bool) ($this->seo['nofollow'] ?? false);
    }

    public function seoInSitemap(): bool
    {
        return ! $this->seoNoindex()
            && ! ($this->seo['exclude_from_sitemap'] ?? false)
            && blank($this->seo['canonical'] ?? null)
            && (bool) (SeoSettings::type($this->seoType())['sitemap'] ?? true);
    }

    public function seoScore(): ?int
    {
        return isset($this->seo['score']) ? (int) $this->seo['score'] : null;
    }

    /** @return array{score: int, checks: array<int, array{status: string, label: string}>} */
    public function seoAnalysis(?array $override = null): array
    {
        $seo = $override ?? (array) ($this->seo ?? []);
        $original = $this->seo;
        $this->seo = $seo;

        try {
            return SeoAnalyzer::analyze([
                'title' => $this->seoTitle(),
                'description' => $this->seoDescription(),
                'slug' => (string) ($this->slug ?? ''),
                'keyphrase' => $seo['focus_keyphrase'] ?? null,
                'html' => $this->seoContentHtml(),
            ]);
        } finally {
            $this->seo = $original;
        }
    }
}
