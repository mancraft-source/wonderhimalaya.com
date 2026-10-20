<?php

namespace Database\Seeders;

use App\Enums\DepartureStatus;
use App\Models\Trip;
use App\Models\TripDeparture;
use Illuminate\Database\Seeder;

/**
 * LOCAL/STAGING LAYOUT PREVIEW ONLY — sample group departures so the departure
 * tables can be reviewed. Real dates come from the client. Refuses to run in production.
 * Remove with: TripDeparture::where('note', self::NOTE)->delete();
 */
class DemoDeparturesSeeder extends Seeder
{
    public const NOTE = 'DEMO — layout preview';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDeparturesSeeder must not run in production.');

            return;
        }

        TripDeparture::where('note', self::NOTE)->delete();

        $statuses = [DepartureStatus::Guaranteed, DepartureStatus::Available, DepartureStatus::Limited, DepartureStatus::Available];

        Trip::published()->where('is_fixed_departure', true)->get()->each(function (Trip $trip, int $i) use ($statuses) {
            foreach ([1, 2, 4, 5] as $k => $months) {
                $start = now()->addMonths($months)->startOfMonth()->addDays(($i * 3 + $k * 5) % 24);
                TripDeparture::create([
                    'trip_id' => $trip->id,
                    'start_date' => $start,
                    'end_date' => $trip->duration_days ? $start->copy()->addDays($trip->duration_days - 1) : null,
                    'price' => $trip->displayPrice(),
                    'seats_total' => 12,
                    'seats_booked' => [3, 6, 10, 0][$k],
                    'status' => $statuses[$k],
                    'note' => self::NOTE,
                ]);
            }
        });

        $this->command?->info('Demo departures created: '.TripDeparture::where('note', self::NOTE)->count());
    }
}
