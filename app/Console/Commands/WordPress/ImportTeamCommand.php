<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\Importers\TeamImporter;

class ImportTeamCommand extends WordPressCommand
{
    protected $signature = 'wp:import-team {--fresh : Bypass the WordPress response cache} {--skip-media : Do not download photos} {--dry-run : Roll back all changes}';

    protected $description = 'Import team members from the child pages of /our-team/';

    public function handle(): int
    {
        $this->prepare();
        $stats = $this->transaction(fn () => app(TeamImporter::class)->onProgress($this->progress())->import());
        $this->report($stats, 'Team imported');

        return self::SUCCESS;
    }
}
