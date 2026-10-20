<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores first-touch marketing attribution (UTM, click IDs, landing page,
 * external referrer) in the session so enquiries can be attributed in the CRM.
 */
class CaptureAttribution
{
    private const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession() && ! $request->is('admin*', 'livewire*', 'api/*')) {
            $session = $request->session();

            $utm = array_filter($request->only(self::KEYS));
            if ($utm && ! $session->has('attribution.utm')) {
                $session->put('attribution.utm', array_map(fn ($v) => substr((string) $v, 0, 190), $utm));
            }

            if (! $session->has('attribution.landing_page')) {
                $session->put('attribution.landing_page', substr($request->fullUrl(), 0, 1000));
                $referrer = (string) $request->headers->get('referer');
                if ($referrer && parse_url($referrer, PHP_URL_HOST) !== $request->getHost()) {
                    $session->put('attribution.referrer', substr($referrer, 0, 1000));
                }
            }
        }

        return $next($request);
    }
}
