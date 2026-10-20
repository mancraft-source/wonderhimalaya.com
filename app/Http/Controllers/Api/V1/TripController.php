<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DepartureStatus;
use App\Http\Resources\V1\DepartureResource;
use App\Http\Resources\V1\TripDetailResource;
use App\Http\Resources\V1\TripResource;
use App\Models\Destination;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Trips
 */
class TripController extends ApiController
{
    /**
     * List trips.
     *
     * Filters: `filter[destination]` (slug, includes child regions), `filter[activity]`, `filter[difficulty]`,
     * `filter[tier]` (slugs), `filter[fixed_departure]`, `filter[featured]` (bool), `filter[duration_min]`,
     * `filter[duration_max]` (days), `filter[price_max]`, `filter[search]` (title), `filter[month]` (YYYY-MM with an
     * upcoming departure). Sorts: `price`, `duration`, `title`, `published_at`, `sort` (prefix `-` for descending).
     * Includes: `destination`, `destinations`, `activities`, `difficulty`, `tiers`. `per_page` max 50.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $trips = QueryBuilder::for(Trip::query()->published()->with('media'))
            ->allowedFilters(
                AllowedFilter::callback('destination', fn (Builder $q, $value) => self::filterDestination($q, (string) $value)),
                AllowedFilter::callback('activity', fn (Builder $q, $value) => $q->whereHas('activities', fn ($a) => $a->whereIn('slug', (array) $value))),
                AllowedFilter::callback('difficulty', fn (Builder $q, $value) => $q->whereHas('difficulty', fn ($d) => $d->whereIn('slug', (array) $value))),
                AllowedFilter::callback('tier', fn (Builder $q, $value) => $q->whereHas('tiers', fn ($t) => $t->whereIn('slug', (array) $value))),
                AllowedFilter::callback('fixed_departure', fn (Builder $q, $value) => $q->where('is_fixed_departure', self::bool($value))),
                AllowedFilter::callback('featured', fn (Builder $q, $value) => $q->where('is_featured', self::bool($value))),
                AllowedFilter::callback('duration_min', fn (Builder $q, $value) => $q->where('duration_days', '>=', (int) $value)),
                AllowedFilter::callback('duration_max', fn (Builder $q, $value) => $q->where('duration_days', '<=', (int) $value)),
                // Numeric literal (not a binding) so SQLite compares numerically; the value is cast, so it is safe.
                AllowedFilter::callback('price_max', fn (Builder $q, $value) => $q->whereRaw('COALESCE(sale_price, price_from) <= '.number_format((float) (is_array($value) ? reset($value) : $value), 2, '.', ''))),
                AllowedFilter::callback('search', fn (Builder $q, $value) => $q->where('title', 'like', '%'.self::like(implode(' ', (array) $value)).'%')),
                AllowedFilter::callback('month', fn (Builder $q, $value) => self::filterMonth($q, (string) $value)),
            )
            ->allowedSorts(
                AllowedSort::callback('price', fn (Builder $q, bool $desc) => $q->orderByRaw('COALESCE(sale_price, price_from) IS NULL')
                    ->orderByRaw('COALESCE(sale_price, price_from) '.($desc ? 'desc' : 'asc'))),
                AllowedSort::field('duration', 'duration_days'),
                AllowedSort::field('title'),
                AllowedSort::field('published_at'),
                AllowedSort::field('sort'),
            )
            ->defaultSort('sort', '-published_at')
            ->allowedIncludes('destination', 'destinations', 'activities', 'difficulty', 'tiers')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return TripResource::collection($trips);
    }

    /** Trip detail with itinerary, FAQs, prices, upcoming departures, media and SEO. */
    public function show(string $slug): TripDetailResource
    {
        $trip = Trip::query()->published()->where('slug', $slug)
            ->with([
                'media', 'difficulty', 'destination', 'destinations', 'activities', 'tiers',
                'days', 'faqs', 'prices.tier', 'upcomingDepartures.tier',
                'related' => fn ($q) => $q->published()->with('media'),
            ])
            ->firstOrFail();

        return new TripDetailResource($trip);
    }

    /** Upcoming departures of one trip. */
    public function departures(string $slug): AnonymousResourceCollection
    {
        $trip = Trip::query()->published()->where('slug', $slug)->firstOrFail();

        return DepartureResource::collection($trip->upcomingDepartures()->with('tier', 'trip')->get());
    }

    public static function filterDestination(Builder $query, string $slug): void
    {
        $ids = self::destinationIds($slug);

        $query->where(fn (Builder $q) => $q->whereIn('destination_id', $ids)
            ->orWhereHas('destinations', fn ($d) => $d->whereIn('destinations.id', $ids)));
    }

    /** @return array<int, int> the destination and all of its descendants */
    public static function destinationIds(string $slug): array
    {
        $root = Destination::where('slug', $slug)->value('id');

        if (! $root) {
            return [0];
        }

        $ids = [$root];
        $frontier = [$root];

        for ($depth = 0; $depth < 5 && $frontier; $depth++) {
            $frontier = Destination::whereIn('parent_id', $frontier)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    public static function filterMonth(Builder $query, string $month): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $from = $start->isPast() ? today() : $start;

        $query->whereHas('departures', fn ($d) => $d
            ->whereDate('start_date', '>=', $from)
            ->whereDate('start_date', '<=', $start->copy()->endOfMonth())
            ->whereNot('status', DepartureStatus::Cancelled));
    }

    public static function bool(mixed $value): bool
    {
        return filter_var(is_array($value) ? reset($value) : $value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function like(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($value));
    }
}
