<?php

namespace App\Services\WordPress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Downloads WordPress uploads once and attaches them to models through
 * spatie/laravel-medialibrary (conversions are queued as configured), or
 * mirrors inline content images to the public disk under wp-content/.
 *
 * Keeps an old URL → new URL map in storage/app/private/wp-media-map.json.
 */
class MediaImporter
{
    protected bool $enabled = true;

    /** @var array<string, string>|null */
    protected ?array $map = null;

    protected bool $dirty = false;

    /** @var array<string, int> */
    protected array $stats = ['downloaded' => 0, 'attached' => 0, 'reused' => 0, 'mirrored' => 0, 'failed' => 0];

    /** @var array<int, string> */
    protected array $failures = [];

    public function __construct(protected WordPressSource $source) {}

    public function enable(bool $enabled = true): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        return $this->stats;
    }

    /** @return array<int, string> */
    public function failures(): array
    {
        return $this->failures;
    }

    /**
     * Attach one remote image to a collection, skipping it when the model
     * already has media with the same original URL.
     */
    public function attach(HasMedia&Model $model, string $collection, ?string $url, ?string $alt = null, ?int $wpId = null): ?Media
    {
        if (! $this->enabled || ! $url) {
            return null;
        }

        $url = $this->bestUrl($url, $wpId);
        $alt = $alt !== null ? TitleCleaner::text($alt) : null;

        $existing = $model->getMedia($collection)->first(fn (Media $m) => $m->getCustomProperty('wp_url') === $url);

        if ($existing) {
            if ($alt !== null && $existing->getCustomProperty('alt') !== $alt) {
                $existing->setCustomProperty('alt', $alt)->save();
            }

            $this->stats['reused']++;
            $this->remember($url, $existing->getUrl());

            return $existing;
        }

        $local = $this->download($url);

        if ($local === null) {
            return null;
        }

        try {
            $fileName = $this->fileName($url);
            $media = $model->addMedia($local)
                ->preservingOriginal()
                ->usingName($alt ?: pathinfo($fileName, PATHINFO_FILENAME))
                ->usingFileName($fileName)
                ->withCustomProperties(array_filter(['alt' => $alt ?? '', 'wp_url' => $url, 'wp_id' => $wpId], fn ($v) => $v !== null))
                ->toMediaCollection($collection);
        } catch (\Throwable $e) {
            $this->fail($url, $e->getMessage());

            return null;
        }

        $this->stats['attached']++;
        $this->remember($url, $media->getUrl());

        return $media;
    }

    /**
     * Sync a whole collection (e.g. a gallery) to the given list, in order.
     * Media that came from WordPress but is no longer listed is removed.
     *
     * @param  array<int, array{url: string, alt?: string|null, id?: int|null}>  $items
     */
    public function sync(HasMedia&Model $model, string $collection, array $items): int
    {
        if (! $this->enabled) {
            return 0;
        }

        $ids = [];

        foreach ($items as $item) {
            $media = $this->attach($model, $collection, $item['url'] ?? null, $item['alt'] ?? null, $item['id'] ?? null);

            if ($media) {
                $ids[] = $media->id;
            }
        }

        $model->load('media');

        foreach ($model->getMedia($collection) as $media) {
            if ($media->getCustomProperty('wp_url') && ! in_array($media->id, $ids, true)) {
                $media->delete();
            }
        }

        if (count($ids) > 1) {
            Media::setNewOrder($ids);
        }

        return count($ids);
    }

    /**
     * Copy an inline content image to the public disk (keeping its upload
     * path) and return the new relative URL, e.g. /storage/wp-content/uploads/2025/02/x.jpg.
     */
    public function mirror(string $url, ?int $wpId = null): ?string
    {
        if (! $this->enabled || ! $this->isOldSiteUpload($url)) {
            return null;
        }

        $map = $this->map();

        if (isset($map[$url])) {
            return $map[$url];
        }

        $best = $this->bestUrl($url, $wpId);

        if (isset($map[$best]) && str_starts_with($map[$best], '/storage/wp-content/')) {
            return $this->remember($url, $map[$best]);
        }

        $relative = self::safePath((string) parse_url($best, PHP_URL_PATH));
        $disk = Storage::disk('public');

        if (! $disk->exists($relative)) {
            $local = $this->download($best);

            if ($local === null) {
                return null;
            }

            $disk->put($relative, File::get($local));
        }

        $this->stats['mirrored']++;
        $new = '/storage/'.$relative;
        $this->remember($best, $new);

        return $this->remember($url, $new);
    }

    /** Resolver for HtmlCleaner: old image URL → new URL. */
    public function resolver(): ?\Closure
    {
        return $this->enabled ? fn (string $url, ?int $wpId = null) => $this->mirror($url, $wpId) : null;
    }

    /**
     * Prefer the full-size original: the WP attachment's source_url when the
     * id is known, or the URL without a "-1024x683" size suffix when that
     * original exists in the media library.
     */
    public function bestUrl(string $url, ?int $wpId = null): string
    {
        $url = html_entity_decode(trim($url));

        if (! $this->isOldSiteUpload($url)) {
            return $url;
        }

        if ($wpId && ($item = $this->source->mediaItem($wpId)) && $this->sameImage($item['url'], $url)) {
            return $item['url'];
        }

        if (preg_match('/^(.*)-\d{2,4}x\d{2,4}(\.[a-z0-9]+)$/i', $url, $m)) {
            $byUrl = $this->source->mediaByUrl();

            foreach ([$m[1].$m[2], $m[1].'-scaled'.$m[2]] as $candidate) {
                if (isset($byUrl[$candidate])) {
                    return $candidate;
                }
            }
        }

        return $url;
    }

    /** Is $candidate the same upload as $url (ignoring size/scaled suffixes)? */
    protected function sameImage(string $candidate, string $url): bool
    {
        $strip = fn (string $u) => preg_replace('/(-\d{2,4}x\d{2,4}|-scaled|-e\d{10,})+(\.[a-z0-9]+)$/i', '$2', $u);

        return $strip($candidate) === $strip($url);
    }

    public function isOldSiteUpload(string $url): bool
    {
        $host = preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));
        $old = preg_replace('/^www\./', '', $this->source->client->host());

        return $host === $old && str_contains((string) parse_url($url, PHP_URL_PATH), '/wp-content/uploads/');
    }

    /** Download (or reuse the cached copy) and return the local path. */
    public function download(string $url): ?string
    {
        $relative = self::safePath((string) parse_url($url, PHP_URL_PATH)) ?: 'misc/'.sha1($url);
        $path = rtrim((string) config('services.wordpress.media_cache_path', storage_path('app/private/wp-media-cache')), '/').'/'.$relative;

        if (File::exists($path) && File::size($path) > 0) {
            return $path;
        }

        $body = $this->source->client->raw($this->encodeUrl($url));

        if ($body === null || $body === '' || $this->looksLikeHtml($body)) {
            $this->fail($url, 'download failed');

            return null;
        }

        try {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $body);
        } catch (\Throwable $e) {
            $this->fail($url, $e->getMessage());

            return null;
        }
        $this->stats['downloaded']++;

        return $path;
    }

    /**
     * Local path for an upload URL path: keeps the wp-content/uploads/YYYY/MM
     * structure, but makes non-ASCII file names (e.g. en dashes) filesystem-safe.
     */
    public static function safePath(string $urlPath): string
    {
        $path = ltrim(rawurldecode($urlPath), '/');

        if ($path === '' || preg_match('/^[A-Za-z0-9._\/\-]+$/', $path)) {
            return $path;
        }

        $dir = dirname($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $name = Str::slug(Str::ascii(pathinfo($path, PATHINFO_FILENAME))) ?: 'file';

        return ($dir !== '.' ? $dir.'/' : '').$name.'-'.substr(sha1($path), 0, 6).($ext ? '.'.$ext : '');
    }

    /** Percent-encode non-ASCII characters in the URL path. */
    protected function encodeUrl(string $url): string
    {
        return (string) preg_replace_callback('/[^\x21-\x7E]/', fn ($m) => rawurlencode($m[0]), $url);
    }

    protected function looksLikeHtml(string $body): bool
    {
        $start = ltrim(substr($body, 0, 200));

        return Str::startsWith(strtolower($start), ['<!doctype html', '<html']);
    }

    protected function fileName(string $url): string
    {
        $name = rawurldecode(basename((string) parse_url($url, PHP_URL_PATH)));
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'image';

        return $base.($ext ? '.'.strtolower($ext) : '');
    }

    protected function fail(string $url, string $reason): void
    {
        $this->stats['failed']++;
        $this->failures[] = "{$url} ({$reason})";
    }

    // ------------------------------------------------------------- URL map

    /** @return array<string, string> */
    public function map(): array
    {
        if ($this->map === null) {
            $file = $this->mapPath();
            $this->map = File::exists($file) ? (json_decode(File::get($file), true) ?: []) : [];
        }

        return $this->map;
    }

    public function lookup(string $url): ?string
    {
        return $this->map()[$url] ?? null;
    }

    protected function remember(string $old, string $new): string
    {
        $this->map();
        $path = parse_url($new, PHP_URL_PATH) ?: $new;

        if (($this->map[$old] ?? null) !== $path) {
            $this->map[$old] = $path;
            $this->dirty = true;
        }

        return $path;
    }

    public function saveMap(): void
    {
        if (! $this->dirty || $this->map === null) {
            return;
        }

        ksort($this->map);
        File::ensureDirectoryExists(dirname($this->mapPath()));
        File::put($this->mapPath(), json_encode($this->map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->dirty = false;
    }

    protected function mapPath(): string
    {
        return (string) config('services.wordpress.media_map_path', storage_path('app/private/wp-media-map.json'));
    }
}
