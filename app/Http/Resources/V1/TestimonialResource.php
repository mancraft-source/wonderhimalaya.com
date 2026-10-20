<?php

namespace App\Http\Resources\V1;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Testimonial */
class TestimonialResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'author_country' => $this->author_country,
            'title' => $this->title,
            'body' => $this->body,
            'rating' => $this->rating,
            'source' => $this->source,
            'source_url' => $this->source_url,
            'reviewed_at' => $this->reviewed_at?->toDateString(),
            'is_featured' => $this->is_featured,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'title' => $this->trip->title, 'slug' => $this->trip->slug] : null),
        ];
    }
}
