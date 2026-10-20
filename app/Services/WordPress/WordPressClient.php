<?php

namespace App\Services\WordPress;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Thin, cached HTTP client for the legacy WordPress site.
 *
 * Every JSON/HTML response is written to the cache directory so the
 * migration can be re-run quickly (and offline). Set
 * `services.wordpress.fresh` to true (the `--fresh` flag) to bypass reads.
 */
class WordPressClient
{
    public const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    /** Timestamp (µs) of the last network request, for the polite delay. */
    protected static float $lastRequestAt = 0;

    public function baseUrl(): string
    {
        return rtrim((string) config('services.wordpress.url', 'https://wonderhimalaya.com'), '/');
    }

    public function host(): string
    {
        return (string) parse_url($this->baseUrl(), PHP_URL_HOST);
    }

    /** GET a REST route (relative to /wp-json/) and decode it. */
    public function get(string $route, array $query = []): mixed
    {
        return $this->getWithHeaders($route, $query)['body'];
    }

    /**
     * Fetch every page of a collection route using X-WP-TotalPages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(string $route, array $query = []): array
    {
        $items = [];
        $page = 1;

        do {
            $result = $this->getWithHeaders($route, $query + ['per_page' => 100, 'page' => $page]);
            $body = $result['body'];

            if (! is_array($body)) {
                break;
            }

            array_push($items, ...array_values($body));
            $totalPages = (int) ($result['total_pages'] ?? 1);
            $page++;
        } while ($page <= $totalPages);

        return $items;
    }

    /** Fetch the rendered HTML of a public URL or path on the old site. */
    public function html(string $urlOrPath): string
    {
        $url = $this->absolute($urlOrPath);
        $key = 'html-'.$this->cacheKey((string) parse_url($url, PHP_URL_PATH) ?: 'home');

        if (($cached = $this->readCache($key, 'html')) !== null) {
            return $cached;
        }

        $html = $this->send($url)->throw()->body();
        $this->writeCache($key, 'html', $html);

        return $html;
    }

    /** Raw body of any URL (sitemaps, images). Returns null on failure. */
    public function raw(string $url): ?string
    {
        try {
            $response = $this->send($this->absolute($url));
        } catch (\Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    public function absolute(string $urlOrPath): string
    {
        if (Str::startsWith($urlOrPath, ['http://', 'https://'])) {
            return $urlOrPath;
        }

        return $this->baseUrl().'/'.ltrim($urlOrPath, '/');
    }

    public function request(): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'application/json, text/html;q=0.9, */*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])
            ->timeout((int) config('services.wordpress.timeout', 30))
            ->connectTimeout(15)
            // Retries 5xx/429 responses and timeouts (ConnectionException) with backoff.
            ->retry(4, fn (int $attempt) => min(8000, 1000 * 2 ** ($attempt - 1)), throw: false);
    }

    /**
     * GET with a polite pause between uncached network requests, plus one
     * extra long back-off if the site keeps timing out.
     */
    protected function send(string $url, array $query = []): Response
    {
        $delayMs = (int) config('services.wordpress.delay_ms', 300);
        $wait = ($delayMs * 1000) - (int) ((microtime(true) - self::$lastRequestAt) * 1_000_000);

        if ($delayMs > 0 && $wait > 0) {
            usleep($wait);
        }

        try {
            return $this->request()->get($url, $query);
        } catch (ConnectionException $e) {
            sleep((int) config('services.wordpress.timeout_backoff', 15));

            return $this->request()->get($url, $query);
        } finally {
            self::$lastRequestAt = microtime(true);
        }
    }

    /** @return array{body: mixed, total_pages: int|null} */
    protected function getWithHeaders(string $route, array $query = []): array
    {
        ksort($query);
        $route = trim($route, '/');
        $key = $this->cacheKey($route.($query ? '?'.http_build_query($query) : ''));

        if (($cached = $this->readCache($key, 'json')) !== null) {
            return json_decode($cached, true);
        }

        /** @var Response $response */
        $response = $this->send($this->baseUrl().'/wp-json/'.$route, $query);

        // WordPress answers 400 when asking for a page past the last one.
        if ($response->status() === 400 && ($query['page'] ?? 1) > 1) {
            return ['body' => [], 'total_pages' => 0];
        }

        if (! $response->successful()) {
            throw new RuntimeException("WordPress request failed [{$response->status()}]: {$route}");
        }

        $result = [
            'body' => $response->json(),
            'total_pages' => $response->header('X-WP-TotalPages') !== '' ? (int) $response->header('X-WP-TotalPages') : null,
        ];

        $this->writeCache($key, 'json', json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $result;
    }

    protected function cacheKey(string $value): string
    {
        $slug = Str::of($value)->replace(['/', '?', '&', '='], '-')->slug()->limit(120, '')->toString();

        return ($slug ?: 'root').'-'.substr(sha1($value), 0, 8);
    }

    protected function cacheEnabled(): bool
    {
        return (bool) config('services.wordpress.cache', true);
    }

    protected function readCache(string $key, string $ext): ?string
    {
        if (! $this->cacheEnabled() || config('services.wordpress.fresh')) {
            return null;
        }

        $file = $this->cachePath("{$key}.{$ext}");

        return File::exists($file) ? File::get($file) : null;
    }

    protected function writeCache(string $key, string $ext, string $contents): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        File::ensureDirectoryExists($this->cachePath());
        File::put($this->cachePath("{$key}.{$ext}"), $contents);
    }

    protected function cachePath(string $file = ''): string
    {
        $dir = rtrim((string) config('services.wordpress.cache_path', storage_path('app/private/wp-cache')), '/');

        return $file === '' ? $dir : $dir.'/'.$file;
    }
}
