<?php

namespace App\Console\Commands\WordPress;

use App\Models\Trip;
use App\Models\TripDeparture;
use App\Models\TripPrice;
use App\Services\WordPress\Importers\TripImporter;
use Database\Seeders\DemoDeparturesSeeder;
use Database\Seeders\DemoGroupPricingSeeder;

/**
 * Brings trip packages (names, adult/child prices, descriptions, minimum group size)
 * and the departure set-up (daily vs scheduled, cut-off, capacity) in line with the live site,
 * without touching any other imported or edited content. Removes local demo rows.
 */
class SyncPackagesCommand extends WordPressCommand
{
    protected $signature = 'wp:sync-packages {--id=* : Only these WordPress trip IDs} {--fresh : Bypass the response cache}';

    protected $description = 'Sync trip packages, prices and departure mode from the live WordPress site';

    public function handle(): int
    {
        $this->prepare();

        $demoPrices = TripPrice::where('sort', '>=', DemoGroupPricingSeeder::SORT)->delete();
        $demoDates = TripDeparture::where('note', DemoDeparturesSeeder::NOTE)->delete();
        $this->line("Removed demo data: {$demoPrices} price bands, {$demoDates} departures.");

        $importer = app(TripImporter::class);
        $trips = Trip::withTrashed()->whereNotNull('wp_id')
            ->when($this->option('id'), fn ($q, $ids) => $q->whereIn('wp_id', $ids))
            ->get();

        $bar = $this->output->createProgressBar($trips->count());
        foreach ($trips as $trip) {
            $importer->syncCommercials($trip);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['', 'Count'], [
            ['Trips synced', $trips->count()],
            ['Price rows', TripPrice::count()],
            ['…with child price', TripPrice::whereNotNull('child_price')->count()],
            ['…with package description', TripPrice::whereNotNull('description')->count()],
            ['Trips departing daily', Trip::where('departure_mode', 'daily')->count()],
        ]);

        return self::SUCCESS;
    }
}
