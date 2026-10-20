<?php

namespace App\Http\Controllers\Site;

use App\Enums\DepartureStatus;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripDeparture;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class DepartureController extends Controller
{
    public function index(): View
    {
        $departures = TripDeparture::query()
            ->with(['trip.destination', 'trip.difficulty', 'trip.media', 'tier'])
            ->whereHas('trip', fn ($q) => $q->published())
            ->whereDate('start_date', '>=', today())
            ->whereNot('status', DepartureStatus::Cancelled)
            ->orderBy('start_date')
            ->get()
            ->groupBy(fn (TripDeparture $d) => $d->start_date->format('Y-m'));

        return view('site.departures', [
            'months' => $departures,
            'fixedTrips' => Trip::published()->where('is_fixed_departure', true)->with(['destination', 'difficulty', 'media'])->orderBy('sort')->get(),
            'seo' => Seo::make('Fixed group departures — join a small group', 'Join a guaranteed small-group departure to Everest, Annapurna, Manaslu, Langtang and more. Fixed dates, shared costs, expert local guides.'),
        ]);
    }
}
