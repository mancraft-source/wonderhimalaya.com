<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\RedirectBuilder;

class BuildRedirectsCommand extends WordPressCommand
{
    protected $signature = 'wp:build-redirects {--fresh : Bypass the WordPress response cache} {--dry-run : Roll back all changes}';

    protected $description = 'Create 301 redirects from old WordPress URLs to the new paths';

    public function handle(): int
    {
        $this->prepare();
        $stats = $this->transaction(fn () => app(RedirectBuilder::class)->import());
        $this->report($stats, 'Redirects built');

        return self::SUCCESS;
    }
}
