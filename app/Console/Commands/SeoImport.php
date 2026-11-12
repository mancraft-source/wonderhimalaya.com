<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Trip;
use App\Support\Seo\SeoSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Signature('seo:import {file=resources/seo/seo-titles-and-meta-descriptions.csv : CSV made by the SEO audit} {--only-blank : Only fill records that have no SEO title/description of their own} {--dry-run : Show what would change without saving}')]
#[Description('Apply the suggested SEO titles, meta descriptions and focus keyphrases from the SEO audit CSV')]
class SeoImport extends Command
{
    /** Admin URL segment ("/admin/{segment}/{id}/edit" in the CSV) → model. */
    private const MODELS = [
        'trips' => Trip::class,
        'destinations' => Destination::class,
        'activities' => Activity::class,
        'pages' => Page::class,
        'posts' => Post::class,
        'team-members' => TeamMember::class,
    ];

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = array_map(fn (string $column) => trim($column, "\u{FEFF} \t"), fgetcsv($handle, escape: '\\'));
        $updated = $skipped = 0;

        while (($values = fgetcsv($handle, escape: '\\')) !== false) {
            if (count($values) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $values);
            $title = trim($row['Paste into admin: SEO title'] ?? '');
            $description = trim($row['Suggested meta description'] ?? '');
            $keyphrase = trim($row['Focus keyphrase'] ?? '');

            if ($title === '' && $description === '') {
                continue;
            }

            if (($row['Type'] ?? '') === 'Listing page') {
                $updated += $this->homepage($row['URL'] ?? '', $title, $description);

                continue;
            }

            $model = $this->record((string) ($row['Where to edit'] ?? ''));

            if (! $model) {
                $this->warn('No record for '.($row['URL'] ?? '?'));
                $skipped++;

                continue;
            }

            $seo = (array) ($model->seo ?? []);

            if ($this->option('only-blank') && (filled($seo['title'] ?? null) || filled($seo['description'] ?? null))) {
                $skipped++;

                continue;
            }

            $seo = array_merge($seo, array_filter(['title' => $title, 'description' => $description, 'focus_keyphrase' => $keyphrase], 'filled'));

            if (! $this->option('dry-run')) {
                $model->seo = $seo;
                $model->save();
            }

            $this->line(sprintf('%-12s %s', class_basename($model), $title));
            $updated++;
        }

        fclose($handle);
        Cache::forget('sitemap.xml');

        $this->info(($this->option('dry-run') ? 'Would update ' : 'Updated ')."{$updated} records, skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function record(string $adminUrl): ?Model
    {
        if (! preg_match('#/admin/([a-z-]+)/(\d+)/edit#', $adminUrl, $m) || ! isset(self::MODELS[$m[1]])) {
            return null;
        }

        return self::MODELS[$m[1]]::query()->find((int) $m[2]);
    }

    /** The homepage's title lives in SEO settings; other listing pages are set in code. */
    private function homepage(string $url, string $title, string $description): int
    {
        if (rtrim(parse_url($url, PHP_URL_PATH) ?: '/', '/') !== '') {
            return 0;
        }

        if (! $this->option('dry-run')) {
            Setting::put('seo', array_replace_recursive(SeoSettings::all(), ['home_title' => $title, 'home_description' => $description]));
        }

        $this->line(sprintf('%-12s %s', 'Homepage', $title));

        return 1;
    }
}
