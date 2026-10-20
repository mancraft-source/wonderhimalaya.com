<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Trip card / list item. @mixin \App\Models\Trip */
class TripResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_code' => $this->trip_code,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'excerpt' => $this->excerpt,
            'url' => $this->url(),
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,
            'duration_label' => $this->durationLabel(),
            'max_altitude_m' => $this->max_altitude_m,
            'group_min' => $this->group_min,
            'group_max' => $this->group_max,
            'best_season' => $this->best_season,
            'currency' => $this->currency,
            'price_from' => $this->price_from,
            'sale_price' => $this->sale_price,
            'display_price' => $this->displayPrice(),
            'is_fixed_departure' => $this->is_fixed_departure,
            'is_featured' => $this->is_featured,
            'is_popular' => $this->is_popular,
            'is_top_selling' => $this->is_top_selling,
            'is_trip_of_month' => $this->is_trip_of_month,
            'published_at' => $this->published_at?->toIso8601String(),
            'hero' => MediaUrls::first($this->resource, 'hero'),
            'difficulty' => new TaxonomyResource($this->whenLoaded('difficulty')),
            'destination' => new DestinationResource($this->whenLoaded('destination')),
            'destinations' => DestinationResource::collection($this->whenLoaded('destinations')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'tiers' => TaxonomyResource::collection($this->whenLoaded('tiers')),
        ];
    }
}
