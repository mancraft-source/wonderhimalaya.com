<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;

class Activity extends Model implements HasMedia
{
    use HasSeo, InteractsWithImages, RecordsSlugRedirects;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
    }

    public function publicPath(): string
    {
        return '/activities/'.$this->slug;
    }
}
