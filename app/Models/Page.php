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
        return ['blocks' => 'array', 'content' => 'array', 'show_in_footer' => 'boolean'];
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
        $segments = [$this->slug];
        $seen = [$this->id];
        $parentId = $this->parent_id;

        // Walk up at most 5 levels and stop on cycles (a page set as its own ancestor).
        while ($parentId && count($segments) < 6 && ! in_array($parentId, $seen, true)) {
            $parent = self::withTrashed()->find($parentId, ['id', 'slug', 'parent_id']);
            if (! $parent) {
                break;
            }
            array_unshift($segments, $parent->slug);
            $seen[] = $parent->id;
            $parentId = $parent->parent_id;
        }

        return trim(implode('/', $segments), '/');
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

    /** @return array<string, string|int|float|null> */
    protected function seoExtraVariables(): array
    {
        return ['parent' => $this->parent_id ? $this->seoRelated('parent', 'title') : null];
    }

    /** Page copy lives in blocks: gather their text fields for the excerpt and content checks. */
    public function seoContentHtml(): string
    {
        $html = '';
        $data = [$this->blocks ?? [], $this->content ?? []];
        array_walk_recursive($data, function (mixed $value, string|int $key) use (&$html): void {
            if (is_string($value) && ! in_array($key, ['type', 'image', 'url', 'link', 'icon', 'style', 'variant'], true) && ! str_starts_with($value, 'http')) {
                $html .= str_contains($value, '<') ? $value : '<p>'.e($value).'</p>';
            }
        });

        return $html;
    }
}
