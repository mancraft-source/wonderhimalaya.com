<?php

namespace App\Support;

/**
 * Collects SEO data for the current page; rendered by <x-seo-head>.
 * Controllers call Seo::make(...) and pass it to the view as $seo.
 */
final class Seo
{
    /** @var array<int, array<string, mixed>> */
    public array $schema = [];

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

    /** @param  object{seo?: ?array}|null  $model */
    public static function for(object $model, string $fallbackTitle, ?string $fallbackDescription = null, ?string $image = null): self
    {
        $data = $model->seo ?? [];

        $seo = self::make($data['title'] ?? $fallbackTitle, $data['description'] ?? $fallbackDescription, $image);
        $seo->canonical = $data['canonical'] ?? null;
        $seo->noindex = (bool) ($data['noindex'] ?? false);

        return $seo;
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
        $brand = config('app.name');

        if (! $this->title) {
            return $brand.' — Treks, Tours & Expeditions in the Himalaya';
        }

        if (str_contains($this->title, $brand)) {
            return $this->title;
        }

        // Keep titles within ~65 characters for search results: drop the brand suffix on long titles.
        return mb_strlen($this->title.' | '.$brand) <= 65 ? $this->title.' | '.$brand : str($this->title)->limit(65, '…')->toString();
    }
}
