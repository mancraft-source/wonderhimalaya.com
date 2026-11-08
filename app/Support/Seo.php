<?php

namespace App\Support;

use App\Support\Seo\SeoSettings;

/**
 * Collects SEO data for the current page; rendered by <x-seo-head>.
 * Controllers call Seo::make(...) and pass it to the view as $seo.
 */
final class Seo
{
    /** @var array<int, array<string, mixed>> */
    public array $schema = [];

    /** Set when the title is already final (rendered from a template), so no brand suffix is added. */
    public bool $titleIsFinal = false;

    public bool $nofollow = false;

    public ?string $ogTitle = null;

    public ?string $ogDescription = null;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $canonical = null,
        public bool $noindex = false,
        public string $type = 'website',
    ) {}

    public static function make(?string $title = null, ?string $description = null, ?string $image = null): self
    {
        return new self($title, $description ? str(html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5))->squish()->limit(158)->toString() : null, $image);
    }

    /**
     * SEO for a content record: resolved title/description templates (see HasSeo), robots, canonical and social overrides.
     * The fallbacks are used only for models without the HasSeo trait.
     */
    public static function for(object $model, string $fallbackTitle, ?string $fallbackDescription = null, ?string $image = null): self
    {
        $data = (array) ($model->seo ?? []);
        $hasSeo = method_exists($model, 'seoTitle');

        $seo = self::make(
            $hasSeo ? $model->seoTitle() : ($data['title'] ?? $fallbackTitle),
            ($hasSeo ? $model->seoDescription() : null) ?? $data['description'] ?? $fallbackDescription,
            filled($data['og_image'] ?? null) ? asset('storage/'.$data['og_image']) : $image,
        );
        $seo->titleIsFinal = $hasSeo;
        $seo->canonical = filled($data['canonical'] ?? null) ? $data['canonical'] : null;
        $seo->noindex = $hasSeo ? $model->seoNoindex() : (bool) ($data['noindex'] ?? false);
        $seo->nofollow = (bool) ($data['nofollow'] ?? false);
        $seo->ogTitle = filled($data['og_title'] ?? null) ? $data['og_title'] : null;
        $seo->ogDescription = filled($data['og_description'] ?? null) ? $data['og_description'] : null;

        return $seo;
    }

    /** Listing pages with filters/sorting: keep them out of the index but let crawlers follow the trip links. */
    public function noindexFollow(): self
    {
        $this->noindex = true;
        $this->nofollow = false;

        return $this;
    }

    public function robots(): string
    {
        if (! SeoSettings::indexable()) {
            return 'noindex, nofollow';
        }

        return $this->noindex
            ? 'noindex, '.($this->nofollow ? 'nofollow' : 'follow')
            : 'index, '.($this->nofollow ? 'nofollow' : 'follow').', max-image-preview:large, max-snippet:-1';
    }

    /** @param array<string, mixed> $schema */
    public function withSchema(array $schema): self
    {
        $this->schema[] = $schema;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function fullTitle(): string
    {
        $brand = SeoSettings::siteName();

        if (! $this->title) {
            return (string) SeoSettings::get('home_title');
        }

        if ($this->titleIsFinal || str_contains($this->title, $brand)) {
            return $this->title;
        }

        // Keep titles within ~65 characters for search results: drop the brand suffix on long titles.
        $suffixed = $this->title.' '.SeoSettings::separator().' '.$brand;

        return mb_strlen($suffixed) <= 65 ? $suffixed : str($this->title)->limit(65, '…')->toString();
    }
}
