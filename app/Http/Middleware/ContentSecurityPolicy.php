<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Spatie\Csp\AddCspHeaders;

/**
 * spatie/laravel-csp with project exclusions: Filament (/admin), Livewire endpoints and
 * the API docs rely on inline scripts/styles, so they get no CSP header from us.
 * Policy lives in App\Support\Csp\SitePolicy; report-only vs enforced is set in config/csp.php.
 */
class ContentSecurityPolicy extends AddCspHeaders
{
    /** @var array<int, string> */
    public const EXCEPT = ['admin', 'admin/*', 'livewire/*', 'livewire-*', 'docs/api', 'docs/api/*', 'filament/*'];

    public function handle(Request $request, Closure $next, ?string $customPreset = null)
    {
        if ($request->is(...self::EXCEPT) || ! config('csp.enabled')) {
            return $next($request);
        }

        if (config('csp.nonce_enabled', true)) {
            Vite::useCspNonce(app('csp-nonce'));
        }

        return parent::handle($request, $next, $customPreset);
    }
}
