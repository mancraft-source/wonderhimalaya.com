<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

class Post extends Model implements HasMedia
{
    use HasPublishStatus, HasSeo, InteractsWithImages, RecordsSlugRedirects, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            if ($post->isDirty('body')) {
                $words = str_word_count(strip_tags((string) $post->body));
                $post->reading_minutes = max(1, (int) ceil($words / 220));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
    }

    public function publicPath(): string
    {
        return '/blog/'.$this->slug;
    }
}
