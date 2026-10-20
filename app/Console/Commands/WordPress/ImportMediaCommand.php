<?php

namespace App\Console\Commands\WordPress;

use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Trip;
use App\Services\WordPress\Importers\PageImporter;
use App\Services\WordPress\Importers\PostImporter;
use App\Services\WordPress\Importers\TaxonomyImporter;
use App\Services\WordPress\Importers\TeamImporter;
use App\Services\WordPress\Importers\TripImporter;
use App\Services\WordPress\ImportStats;

/**
 * Downloads media for content that was imported with --skip-media:
 * hero/gallery/map/photo collections and inline images in bodies.
 */
class ImportMediaCommand extends WordPressCommand
{
    protected $signature = 'wp:import-media
        {--type=* : Limit to trips, posts, pages, team, taxonomies}
        {--fresh : Bypass the WordPress response cache}';

    protected $description = 'Download WordPress media for already-imported content and rewrite inline image URLs';

    public function handle(): int
    {
        $this->prepare();
        $this->media()->enable();
        $types = (array) $this->option('type') ?: ['taxonomies', 'trips', 'pages', 'team', 'posts'];
        $stats = new ImportStats;

        $stats = $this->transaction(function () use ($types, $stats) {
            if (in_array('taxonomies', $types, true)) {
                $importer = app(TaxonomyImporter::class);
                $importer->importMedia();
                $stats->merge($importer->stats());
            }

            $jobs = [
                'trips' => [Trip::class, TripImporter::class],
                'pages' => [Page::class, PageImporter::class],
                'team' => [TeamMember::class, TeamImporter::class],
                'posts' => [Post::class, PostImporter::class],
            ];

            foreach ($jobs as $type => [$model, $importerClass]) {
                if (! in_array($type, $types, true)) {
                    continue;
                }

                $importer = app($importerClass);
                $records = $model::whereNotNull('wp_id')->get();
                $this->components->task("Media for {$records->count()} {$type}", function () use ($records, $importer) {
                    foreach ($records as $record) {
                        $importer->importMedia($record);
                    }
                });
                $stats->merge($importer->stats());
            }

            return $stats;
        });

        $this->report($stats, 'Media imported');

        return self::SUCCESS;
    }
}
