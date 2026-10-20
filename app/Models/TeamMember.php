<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class TeamMember extends Model implements HasMedia
{
    use HasSeo, InteractsWithImages, RecordsSlugRedirects;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'certifications' => 'array',
            'social' => 'array',
            'is_guide' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function publicPath(): string
    {
        return '/our-team/'.$this->slug;
    }
}
