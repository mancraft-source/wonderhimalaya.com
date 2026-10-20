<?php

namespace App\Http\Resources\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Post */
class PostResource extends JsonResource
{
    public bool $detailed = false;

    public function detailed(): static
    {
        $this->detailed = true;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'url' => url($this->publicPath()),
            'reading_minutes' => $this->reading_minutes,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'hero' => MediaUrls::first($this->resource, 'hero'),
            'category' => new TaxonomyResource($this->whenLoaded('category')),
            'tags' => TaxonomyResource::collection($this->whenLoaded('tags')),
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'name' => $this->author->name,
                'slug' => $this->author->slug,
                'role' => $this->author->role,
            ] : null),
            $this->mergeWhen($this->detailed, fn () => [
                'body' => $this->body,
                'seo' => SeoData::for($this->resource),
            ]),
        ];
    }
}
