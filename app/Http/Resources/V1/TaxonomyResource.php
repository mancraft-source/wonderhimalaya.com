<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Difficulty, Tier, Tag, PostCategory — small lookup records. */
class TaxonomyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'level' => $this->when(array_key_exists('level', $attributes), fn () => $this->level),
            'description' => $this->when(array_key_exists('description', $attributes), fn () => $this->description),
        ];
    }
}
