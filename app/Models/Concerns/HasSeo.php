<?php

namespace App\Models\Concerns;

/**
 * Stores per-record SEO overrides in a `seo` JSON column:
 * title, description, canonical, og_image, noindex.
 */
trait HasSeo
{
    public function initializeHasSeo(): void
    {
        $this->mergeCasts(['seo' => 'array']);
    }

    public function seoTitle(): string
    {
        return $this->seo['title'] ?? $this->title ?? $this->name ?? config('app.name');
    }

    public function seoDescription(): ?string
    {
        return $this->seo['description'] ?? null;
    }

    public function seoNoindex(): bool
    {
        return (bool) ($this->seo['noindex'] ?? false);
    }
}
