<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * CMS page composed of content blocks. `path` is the full URL path
 * (e.g. "travel-guide/nepal-visa") and is rebuilt from the parent chain.
 */
class Page extends Model implements HasMedia
{
    use HasPublishStatus, HasSeo, InteractsWithImages, RecordsSlugRedirects, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'show_in_footer' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            $page->path = $page->buildPath();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function buildPath(): string
    {
        $parent = $this->parent_id ? self::find($this->parent_id) : null;

        return ltrim(($parent ? $parent->buildPath().'/' : '').$this->slug, '/');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('images');
    }

    public function publicPath(): string
    {
        return $this->path === 'home' ? '/' : '/'.$this->path;
    }
}
