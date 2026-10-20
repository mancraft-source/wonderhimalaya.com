<?php

namespace App\Http\Resources\V1;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'icon' => $this->icon,
            'is_featured' => $this->is_featured,
            'url' => url($this->publicPath()),
            'trips_count' => $this->whenCounted('trips'),
            'hero' => MediaUrls::first($this->resource, 'hero'),
            $this->mergeWhen($this->detailed, fn () => [
                'description' => $this->description,
                'seo' => SeoData::for($this->resource),
            ]),
        ];
    }
}
