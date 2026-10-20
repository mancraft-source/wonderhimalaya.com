<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\ReviewImporter;

class ImportReviewsCommand extends WordPressCommand
{
    protected $signature = 'wp:import-reviews {--fresh : Bypass the WordPress response cache} {--dry-run : Roll back all changes}';

    protected $description = 'Import the Google reviews shown by Trustindex on the old homepage as testimonials';

    public function handle(): int
    {
        $this->prepare();
        $stats = $this->transaction(fn () => app(ReviewImporter::class)->import());
        $this->report($stats, 'Reviews imported');

        return self::SUCCESS;
    }
}
