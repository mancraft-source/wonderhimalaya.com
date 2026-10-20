<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\PageImporter;

class ImportPagesCommand extends WordPressCommand
{
    protected $signature = 'wp:import-pages {--fresh : Bypass the WordPress response cache} {--skip-media : Do not download images} {--dry-run : Roll back all changes}';

    protected $description = 'Import WordPress pages (guides, legal, about…) as block-based pages';

    public function handle(): int
    {
        $this->prepare();
        $stats = $this->transaction(fn () => app(PageImporter::class)->onProgress($this->progress())->import());
        $this->report($stats, 'Pages imported');

        return self::SUCCESS;
    }
}
