<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\PostImporter;

class ImportPostsCommand extends WordPressCommand
{
    protected $signature = 'wp:import-posts {--fresh : Bypass the WordPress response cache} {--skip-media : Do not download images} {--dry-run : Roll back all changes}';

    protected $description = 'Import blog posts (categories, tags, hero and inline images)';

    public function handle(): int
    {
        $this->prepare();
        $stats = $this->transaction(fn () => app(PostImporter::class)->onProgress($this->progress())->import());
        $this->report($stats, 'Posts imported');

        return self::SUCCESS;
    }
}
