<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

class Destination extends Model implements HasMedia
{
    use HasSeo, InteractsWithImages, RecordsSlugRedirects;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['highlights' => 'array', 'is_featured' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
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
        return '/destination/'.$this->slug;
    }

    /** @return array<string, string|int|float|null> */
    protected function seoExtraVariables(): array
    {
        return ['parent' => $this->parent_id ? $this->seoRelated('parent') : null];
    }
}
