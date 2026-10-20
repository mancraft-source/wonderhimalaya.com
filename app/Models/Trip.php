<?php

namespace App\Models;

use App\Enums\DepartureStatus;
use App\Models\Concerns\HasPublishStatus;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

class Trip extends Model implements HasMedia
{
    use HasPublishStatus, HasSeo, InteractsWithImages, LogsActivity, RecordsSlugRedirects, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'includes' => 'array',
            'excludes' => 'array',
            'facts' => 'array',
            'videos' => 'array',
            'price_from' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_fixed_departure' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'is_top_selling' => 'boolean',
            'is_trip_of_month' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['title', 'slug', 'status', 'price_from', 'sale_price']);
    }

    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(Difficulty::class);
    }

    /** Primary region used for breadcrumbs and cards. */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }

    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(Tier::class)->orderBy('sort');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function days(): HasMany
    {
        return $this->hasMany(TripDay::class)->orderBy('sort')->orderBy('day_number');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(TripFaq::class)->orderBy('sort');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(TripPrice::class)->orderBy('sort');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(TripDeparture::class)->orderBy('start_date');
    }

    public function upcomingDepartures(): HasMany
    {
        return $this->departures()
            ->whereDate('start_date', '>=', today())
            ->whereNot('status', DepartureStatus::Cancelled);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'related_trips', 'trip_id', 'related_trip_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('map')->singleFile();
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /** Lowest advertised price, preferring an active sale price. */
    public function displayPrice(): ?float
    {
        $price = $this->sale_price ?? $this->price_from;

        return $price !== null ? (float) $price : null;
    }

    public function durationLabel(): ?string
    {
        return $this->duration_days ? $this->duration_days.' '.str('day')->plural($this->duration_days) : null;
    }

    public function publicPath(): string
    {
        return '/trip/'.$this->slug;
    }

    public function url(): string
    {
        return url($this->publicPath());
    }
}
