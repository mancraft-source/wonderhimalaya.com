<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DepartureStatus;
use App\Http\Resources\V1\DepartureResource;
use App\Models\TripDeparture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * @tags Departures
 */
class DepartureController extends ApiController
{
    /**
     * Upcoming departures across published trips.
     *
     * Filters: `month` (YYYY-MM), `trip` (trip slug), `destination` (slug, includes child regions).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'trip' => ['nullable', 'string', 'max:190'],
            'destination' => ['nullable', 'string', 'max:190'],
        ]);

        $query = TripDeparture::query()
            ->whereDate('start_date', '>=', today())
            ->whereNot('status', DepartureStatus::Cancelled)
            ->whereHas('trip', function (Builder $trip) use ($request) {
                $trip->published();

                if ($request->filled('trip')) {
                    $trip->where('slug', $request->string('trip'));
                }

                if ($request->filled('destination')) {
                    TripController::filterDestination($trip, (string) $request->string('destination'));
                }
            })
            ->with(['tier', 'trip.media'])
            ->orderBy('start_date');

        if ($request->filled('month')) {
            $start = Carbon::createFromFormat('Y-m-d', $request->string('month').'-01')->startOfDay();
            $query->whereBetween('start_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        }

        return DepartureResource::collection($query->paginate($this->perPage($request, 20))->withQueryString());
    }
}
