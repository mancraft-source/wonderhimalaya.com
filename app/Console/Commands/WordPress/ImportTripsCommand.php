<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\TripImporter;

class ImportTripsCommand extends WordPressCommand
{
    protected $signature = 'wp:import-trips
        {--id=* : Only import these WordPress trip ids}
        {--fresh : Bypass the WordPress response cache}
        {--skip-media : Do not download hero/gallery/map images}
        {--dry-run : Roll back all changes}';

    protected $description = 'Import WP Travel Engine trips (itineraries, FAQs, prices, media) from WordPress';

    public function handle(): int
    {
        $this->prepare();
        $ids = array_map('intval', (array) $this->option('id'));
        $stats = $this->transaction(fn () => app(TripImporter::class)->onProgress($this->progress())->import($ids));
        $this->report($stats, 'Trips imported');

        return self::SUCCESS;
    }
}
