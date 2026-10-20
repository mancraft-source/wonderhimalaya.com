<?php

namespace App\Http\Resources\V1;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Destination */
class DestinationResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'tagline' => $this->tagline,
            'intro' => $this->intro,
            'is_featured' => $this->is_featured,
            'url' => url($this->publicPath()),
            'trips_count' => $this->whenCounted('trips'),
            'hero' => MediaUrls::first($this->resource, 'hero'),
            'children' => DestinationResource::collection($this->whenLoaded('children')),
            'parent' => new DestinationResource($this->whenLoaded('parent')),
            $this->mergeWhen($this->detailed, fn () => [
                'description' => $this->description,
                'highlights' => $this->highlights,
                'seo' => SeoData::for($this->resource),
            ]),
        ];
    }
}
