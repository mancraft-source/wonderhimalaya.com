<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\ImportStats;
use App\Services\WordPress\MediaImporter;
use App\Services\WordPress\WordPressSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Shared plumbing for the wp:* migration commands: --fresh (bypass the
 * response cache), --dry-run (everything in a rolled-back transaction),
 * --skip-media, and a summary table.
 */
abstract class WordPressCommand extends Command
{
    protected function source(): WordPressSource
    {
        return app(WordPressSource::class);
    }

    protected function media(): MediaImporter
    {
        return app(MediaImporter::class);
    }

    protected function prepare(): void
    {
        // One source/media instance per run so lookups are memoised across sub-commands.
        app()->singletonIf(WordPressSource::class);
        app()->singletonIf(MediaImporter::class);

        if ($this->hasOption('fresh') && $this->option('fresh')) {
            config(['services.wordpress.fresh' => true]);
        }

        $skipMedia = ($this->hasOption('skip-media') && $this->option('skip-media'))
            || ($this->hasOption('dry-run') && $this->option('dry-run'));

        $this->media()->enable(! $skipMedia);

        activity()->disableLogging();
    }

    /** Run $callback, rolling everything back when --dry-run is set. */
    protected function transaction(\Closure $callback): ImportStats
    {
        $dry = $this->hasOption('dry-run') && $this->option('dry-run');

        if ($dry) {
            DB::beginTransaction();
        }

        try {
            $stats = $callback();
        } finally {
            if ($dry) {
                DB::rollBack();
            }
        }

        if (! $dry) {
            $this->media()->saveMap();
        }

        return $stats;
    }

    protected function report(ImportStats $stats, ?string $title = null): void
    {
        if ($title) {
            $this->components->info($title.($this->hasOption('dry-run') && $this->option('dry-run') ? ' (dry run, rolled back)' : ''));
        }

        $this->table(ImportStats::headers(), $stats->tableRows());

        if ($this->media()->enabled()) {
            $media = $this->media()->stats();

            if (array_sum($media) > 0) {
                $this->line(sprintf('  Media: %d downloaded, %d attached, %d reused, %d inline mirrored, %d failed', ...array_values($media)));
            }

            foreach (array_slice($this->media()->failures(), 0, 20) as $failure) {
                $this->components->warn('Media: '.$failure);
            }
        }

        foreach (array_unique($stats->notes) as $note) {
            $this->components->warn($note);
        }
    }

    /** @return \Closure(string): void */
    protected function progress(): \Closure
    {
        return fn (string $message) => $this->output->isVerbose() ? $this->line('  '.$message) : null;
    }
}
