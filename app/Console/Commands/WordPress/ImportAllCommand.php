<?php

namespace App\Console\Commands\WordPress;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Difficulty;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\Tier;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\TripDeparture;
use App\Models\TripFaq;
use App\Models\TripPrice;
use App\Services\WordPress\Importers\RedirectBuilder;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportAllCommand extends WordPressCommand
{
    protected $signature = 'wp:import-all
        {--fresh : Bypass the WordPress response cache}
        {--skip-media : Do not download any media}
        {--report= : Path of the QA CSV (default storage/app/private/migration-report.csv)}';

    protected $description = 'Run the whole WordPress migration (taxonomies, trips, pages, team, posts, reviews, redirects)';

    public function handle(): int
    {
        $this->prepare();
        $options = array_filter([
            '--fresh' => (bool) $this->option('fresh'),
            '--skip-media' => (bool) $this->option('skip-media'),
        ]);
        $started = microtime(true);

        foreach (['wp:import-taxonomies', 'wp:import-trips', 'wp:import-pages', 'wp:import-team', 'wp:import-posts'] as $command) {
            $this->call($command, $options);
        }

        $this->call('wp:import-reviews', array_intersect_key($options, ['--fresh' => 1]));
        $this->call('wp:build-redirects', array_intersect_key($options, ['--fresh' => 1]));

        $this->components->info(sprintf('Migration finished in %.1fs', microtime(true) - $started));
        $this->table(['Model', 'Rows'], collect([
            'trips' => Trip::count(), 'trip_days' => TripDay::count(), 'trip_faqs' => TripFaq::count(),
            'trip_prices' => TripPrice::count(), 'trip_departures' => TripDeparture::count(),
            'destinations' => Destination::count(), 'activities' => Activity::count(), 'difficulties' => Difficulty::count(),
            'tiers' => Tier::count(), 'tags' => Tag::count(), 'post_categories' => PostCategory::count(), 'posts' => Post::count(),
            'pages' => Page::count(), 'team_members' => TeamMember::count(), 'testimonials' => Testimonial::count(),
            'redirects' => Redirect::count(), 'media' => Media::count(),
        ])->map(fn ($n, $k) => [$k, $n])->values()->all());

        $path = $this->writeReport();
        $this->components->info("QA report written to {$path}");

        return self::SUCCESS;
    }

    protected function writeReport(): string
    {
        $path = $this->option('report') ?: storage_path('app/private/migration-report.csv');
        $base = rtrim($this->source()->client->baseUrl(), '/');
        $new = rtrim((string) config('app.url'), '/');

        File::ensureDirectoryExists(dirname($path));
        $handle = fopen($path, 'w');
        fputcsv($handle, ['old_url', 'new_url', 'type', 'title']);

        foreach (app(RedirectBuilder::class)->rows() as $row) {
            fputcsv($handle, [$base.$row['old'].($row['old'] === '/' ? '' : '/'), $new.$row['new'], $row['type'], $row['title']]);
        }

        fclose($handle);

        return $path;
    }
}
