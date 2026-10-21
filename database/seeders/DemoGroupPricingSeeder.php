<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\TripPrice;
use Illuminate\Database\Seeder;

/**
 * LOCAL/STAGING LAYOUT PREVIEW ONLY — adds sample group-size bands (2–5, 6–12 people) beside the
 * imported per-person package prices so the group-pricing UI can be reviewed. Real bands are entered
 * by the client in the admin (Trip → Pricing). Refuses to run in production.
 * Remove with: TripPrice::where('sort', '>=', DemoGroupPricingSeeder::SORT)->delete();
 */
class DemoGroupPricingSeeder extends Seeder
{
    public const SORT = 900;

    public const TRIPS = ['everest-base-camp-trek', 'annapurna-base-camp-trek', 'manaslu-circuit-trek', 'mardi-himal-trek'];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoGroupPricingSeeder must not run in production.');

            return;
        }

        TripPrice::where('sort', '>=', self::SORT)->delete();

        Trip::whereIn('slug', self::TRIPS)->with('prices')->get()->each(function (Trip $trip) {
            foreach ($trip->prices->whereNull('pax_max') as $base) {
                $start = (int) $base->pax_min + 1;
                $mid = max($start + 3, 5);
                foreach ([[$start, $mid, 0.92], [$mid + 1, 12, 0.85]] as $k => [$min, $max, $factor]) {
                    TripPrice::create([
                        'trip_id' => $trip->id,
                        'tier_id' => $base->tier_id,
                        'label' => $base->label,
                        'pax_min' => $min,
                        'pax_max' => $max,
                        'price' => round((float) $base->price * $factor / 5) * 5,
                        'sort' => self::SORT + $k,
                    ]);
                }
            }
        });

        $this->command?->info('Demo group bands: '.TripPrice::where('sort', '>=', self::SORT)->count());
    }
}
