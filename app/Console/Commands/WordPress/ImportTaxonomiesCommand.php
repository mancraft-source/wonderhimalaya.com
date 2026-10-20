<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\TaxonomyImporter;

class ImportTaxonomiesCommand extends WordPressCommand
{
    protected $signature = 'wp:import-taxonomies {--fresh : Bypass the WordPress response cache} {--skip-media : Do not download term images} {--dry-run : Roll back all changes}';

    protected $description = 'Import destinations, activities, difficulties, tiers, tags and post categories from WordPress';

    public function handle(): int
    {
        $this->prepare();
        $stats = $this->transaction(fn () => app(TaxonomyImporter::class)->onProgress($this->progress())->import());
        $this->report($stats, 'Taxonomies imported');

        return self::SUCCESS;
    }
}
