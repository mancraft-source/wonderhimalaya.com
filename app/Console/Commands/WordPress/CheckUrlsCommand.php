<?php

namespace App\Console\Commands\WordPress;

use App\Services\WordPress\UrlChecker;

class CheckUrlsCommand extends WordPressCommand
{
    protected $signature = 'wp:check-urls
        {--sitemap=/sitemap_index.xml : Sitemap index on the old site}
        {--http : Dispatch each path through the Laravel router instead of checking the database}
        {--fresh : Re-download the sitemaps}
        {--all : List every URL, not only the problems}';

    protected $description = 'Check that every URL in the old Yoast sitemaps resolves on the new site';

    public function handle(UrlChecker $checker): int
    {
        $this->prepare();
        $urls = $checker->sitemapUrls((string) $this->option('sitemap'));

        if ($urls === []) {
            $this->components->error('No URLs found in the sitemap.');

            return self::FAILURE;
        }

        $counts = ['ok' => 0, 'redirected' => 0, 'redirect-broken' => 0, 'missing' => 0];
        $rows = [];

        foreach ($urls as $url) {
            $result = $checker->check($url, (bool) $this->option('http'));
            $counts[$result['status']]++;

            if ($this->option('all') || in_array($result['status'], ['missing', 'redirect-broken'], true)) {
                $rows[] = [$result['status'], parse_url($url, PHP_URL_PATH), $result['target'] ?? '—'];
            }
        }

        $this->table(['Status', 'Count'], collect($counts)->map(fn ($n, $s) => [$s, $n])->values()->all());

        if ($rows) {
            $this->table(['Status', 'Old path', 'New target'], $rows);
        }

        $this->components->info(sprintf('%d URLs checked.', count($urls)));

        return $counts['missing'] + $counts['redirect-broken'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
