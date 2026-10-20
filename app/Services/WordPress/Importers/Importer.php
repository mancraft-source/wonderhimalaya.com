<?php

namespace App\Services\WordPress\Importers;

use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ImportStats;
use App\Services\WordPress\MediaImporter;
use App\Services\WordPress\WordPressSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

abstract class Importer
{
    protected ImportStats $stats;

    /** @var (\Closure(string): void)|null */
    protected ?\Closure $logger = null;

    public function __construct(
        protected WordPressSource $source,
        protected MediaImporter $media,
    ) {
        $this->stats = new ImportStats;
    }

    public function stats(): ImportStats
    {
        return $this->stats;
    }

    public function onProgress(?\Closure $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    protected function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
    }

    /** HtmlCleaner wired with the URL map and (when enabled) the image mirror. */
    protected function cleaner(): HtmlCleaner
    {
        return (new HtmlCleaner($this->source->urlMap()))->withImageResolver($this->media->resolver());
    }

    protected function date(array $item): ?CarbonImmutable
    {
        $value = $item['date_gmt'] ?? $item['date'] ?? null;

        return $value ? CarbonImmutable::parse($value, 'UTC') : null;
    }

    /**
     * Find the record for a WP id (including soft-deleted), falling back to
     * an unclaimed record with the same slug so seeded rows are adopted.
     *
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function locate(string $class, int $wpId, ?string $slugColumn = null, ?string $slug = null): Model
    {
        $query = fn () => method_exists($class, 'bootSoftDeletes') ? $class::withTrashed() : $class::query();

        $model = $query()->where('wp_id', $wpId)->first();

        if (! $model && $slugColumn && $slug !== null) {
            $model = $query()->where($slugColumn, $slug)->whereNull('wp_id')->first();
        }

        return $model ?? new $class;
    }

    /** Make $slug unique in $class.$column ignoring the record itself. */
    protected function uniqueSlug(string $class, string $slug, ?int $ignoreId, string $column = 'slug'): string
    {
        $query = fn () => method_exists($class, 'bootSoftDeletes') ? $class::withTrashed() : $class::query();
        $candidate = $slug;

        for ($n = 2; $query()->where($column, $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $n++) {
            $candidate = $slug.'-'.$n;
        }

        return $candidate;
    }
}
