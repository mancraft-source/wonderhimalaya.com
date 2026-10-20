<?php

namespace App\Http\Resources\V1;

use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TeamMember */
class TeamMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'role' => $this->role,
            'excerpt' => $this->excerpt,
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'languages' => $this->languages ?? [],
            'certifications' => $this->certifications ?? [],
            'social' => $this->social ?? [],
            'is_guide' => $this->is_guide,
            'url' => url($this->publicPath()),
            'photo' => MediaUrls::first($this->resource, 'photo'),
        ];
    }
}
