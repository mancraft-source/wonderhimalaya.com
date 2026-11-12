<?php

namespace App\Http\Middleware;

use App\Support\RedirectResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301s GET requests with a trailing slash (WordPress style) or upper-case
 * path to the canonical form, keeping the query string.
 */
class CanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if ($path === '/' || str_starts_with($path, '/admin') || str_starts_with($path, '/livewire') || str_starts_with($path, '/api/')) {
            return $next($request);
        }

        $canonical = rtrim($path, '/');
        if (! str_contains($canonical, '.') && ! str_starts_with($canonical, '/storage')) {
            $canonical = strtolower($canonical);
        }

        if ($canonical !== '' && $canonical !== $path) {
            // Jump straight to a managed redirect target (one hop instead of two).
            if ($managed = RedirectResolver::for($request)) {
                return $managed;
            }

            $query = $request->getQueryString();

            return redirect()->to($canonical.($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
