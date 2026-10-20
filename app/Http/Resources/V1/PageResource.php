<?php

namespace App\Http\Resources\V1;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Page */
class PageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->path,
            'url' => url($this->publicPath()),
            'template' => $this->template,
            'excerpt' => $this->excerpt,
            'blocks' => $this->blocks ?? [],
            'published_at' => $this->published_at?->toIso8601String(),
            'hero' => MediaUrls::first($this->resource, 'hero'),
            'seo' => SeoData::for($this->resource),
        ];
    }
}
