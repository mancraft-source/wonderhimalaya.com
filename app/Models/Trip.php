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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
            'packing' => 'array',
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

    public function departsDaily(): bool
    {
        return $this->departure_mode === 'daily';
    }

    /** Earliest start date a traveller can book (bookings close N days ahead). */
    public function earliestStartDate(): Carbon
    {
        return today()->addDays(max(0, (int) $this->departure_cutoff_days));
    }

    /** Lowest advertised price, preferring an active sale price. */
    public function displayPrice(): ?float
    {
        $price = $this->sale_price ?? $this->price_from;

        return $price !== null ? (float) $price : null;
    }

    /**
     * Per-person price for a group size (and optional package/tier), from the group-pricing bands.
     * Falls back to the advertised price when no band matches.
     */
    public function priceFor(int $pax, ?int $tierId = null): ?float
    {
        $pax = max(1, $pax);
        $rows = $this->relationLoaded('prices') ? $this->prices : $this->prices()->get();

        $candidates = $rows->filter(fn (TripPrice $p) => $tierId === null || (int) $p->tier_id === $tierId);
        if ($candidates->isEmpty()) {
            $candidates = $rows;
        }

        $match = $candidates
            ->filter(fn (TripPrice $p) => $pax >= (int) $p->pax_min && ($p->pax_max === null || $pax <= (int) $p->pax_max))
            ->sortBy(fn (TripPrice $p) => $p->effectivePrice())
            ->first();

        return $match?->effectivePrice() ?? $this->displayPrice();
    }

    /**
     * Group pricing grouped by package: [{key, name, tier_id, bands: [{min, max, label, price, was}]}].
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pricingMatrix(): Collection
    {
        $rows = $this->relationLoaded('prices') ? $this->prices : $this->prices()->with('tier')->get();

        return $rows
            ->groupBy(fn (TripPrice $p) => $p->tier_id ? 'tier-'.$p->tier_id : 'label-'.str($p->label ?: 'Package')->slug())
            ->map(function ($bands, string $key) {
                $first = $bands->first();

                return [
                    'key' => $key,
                    'name' => $first->tier?->name ?? ($first->label ?: 'Package'),
                    'tier_id' => $first->tier_id,
                    'bands' => self::bandRows($bands->sortBy('pax_min')->values()),
                    'from' => $bands->min(fn (TripPrice $p) => $p->effectivePrice()),
                    'child' => $bands->pluck('child_price')->filter()->min() !== null ? (float) $bands->pluck('child_price')->filter()->min() : null,
                    'min' => (int) $bands->min('pax_min'),
                    'description' => $bands->pluck('description')->filter()->first(),
                    'primary' => (bool) $bands->firstWhere('is_primary', true),
                ];
            })
            ->sortBy(fn (array $p) => [! $p['primary'], $p['from']])
            ->values();
    }

    /**
     * Display rows for one package's bands. An open-ended band followed by larger bands is
     * capped visually at the next band's minimum (e.g. "1+" before "2–5" reads "1 pax").
     *
     * @param  Collection<int, TripPrice>  $bands
     * @return array<int, array{min: int, max: ?int, label: string, price: float, was: ?float}>
     */
    private static function bandRows(Collection $bands): array
    {
        return $bands->map(function (TripPrice $p, int $i) use ($bands) {
            $min = (int) $p->pax_min;
            $max = $p->pax_max !== null ? (int) $p->pax_max : null;
            $next = $bands->get($i + 1);
            if ($max === null && $next && (int) $next->pax_min > $min) {
                $max = (int) $next->pax_min - 1;
            }

            return [
                'min' => $min,
                'max' => $max,
                'label' => $max === null ? ($min > 1 ? $min.'+ people' : 'Per person') : ($min === $max ? $min.' '.str('person')->plural($min) : $min.'–'.$max.' people'),
                'price' => $p->effectivePrice(),
                'was' => $p->sale_price !== null && (float) $p->sale_price < (float) $p->price ? (float) $p->price : null,
            ];
        })->all();
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

    /** @return array<string, string|int|float|null> */
    protected function seoExtraVariables(): array
    {
        $price = $this->sale_price ?: $this->price_from;

        return [
            'duration' => $this->duration_days ? $this->duration_days.' Days' : null,
            'price' => $price ? ($this->currency === 'USD' || ! $this->currency ? 'US$' : $this->currency.' ').number_format((float) $price) : null,
            'destination' => str_replace(' Region', '', (string) $this->seoRelated('destination')) ?: null,
            'difficulty' => $this->seoRelated('difficulty'),
            'max_altitude' => $this->max_altitude_m ? number_format($this->max_altitude_m).' m' : null,
            'trip_code' => $this->trip_code,
        ];
    }
}
