<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** A URL visitors requested that doesn't exist (SEO → 404 monitor); one row per path with a hit counter. */
class NotFoundLog extends Model
{
    protected $guarded = ['id'];

    /** Paths never worth logging: framework internals, assets and common vulnerability probes. */
    private const IGNORED_PREFIXES = ['admin', 'livewire', 'build', 'storage', 'api', 'filament', '.well-known', 'wp-admin', 'wp-login', 'xmlrpc', 'cgi-bin', '.env', '.git', 'vendor'];

    private const IGNORED_EXTENSIONS = ['js', 'css', 'map', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif', 'ico', 'woff', 'woff2', 'ttf', 'php', 'env', 'sql', 'zip', 'bak', 'ini', 'yml', 'yaml'];

    protected function casts(): array
    {
        return ['is_ignored' => 'boolean', 'last_hit_at' => 'datetime'];
    }

    public static function record(Request $request): void
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        $path = Redirect::normalize($request->getPathInfo());
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        if ($path === '/' || mb_strlen($path) > 255 || Str::startsWith(ltrim($path, '/'), self::IGNORED_PREFIXES) || in_array($extension, self::IGNORED_EXTENSIONS, true)) {
            return;
        }

        try {
            $log = self::firstOrNew(['path' => $path]);
            $log->hits = $log->exists ? $log->hits + 1 : 1;
            $log->referrer = Str::limit((string) $request->headers->get('referer'), 497) ?: $log->referrer;
            $log->user_agent = Str::limit((string) $request->userAgent(), 252) ?: null;
            $log->last_hit_at = now();
            $log->save();
        } catch (\Throwable) {
            // Logging must never turn a 404 into a 500.
        }
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_ignored', false);
    }
}
