<?php

use App\Http\Middleware\CanonicalUrl;
use App\Http\Middleware\CaptureAttribution;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureApiUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Support\RedirectResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // --- Public site (lead dev) ---
        $middleware->prepend(CanonicalUrl::class);
        $middleware->web(append: [CaptureAttribution::class]);

        // --- API / payments / security (api agent) ---
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [ContentSecurityPolicy::class]);
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'api.active' => EnsureApiUserIsActive::class,
        ]);
        // Gateway callbacks are verified by signature instead of CSRF (bank gateways POST back cross-site).
        $middleware->validateCsrfTokens(except: ['webhooks/*', 'pay/*/card/return']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Managed 301s (migrated WordPress URLs, renamed slugs) before showing a 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*', 'admin*', 'livewire*')) {
                return RedirectResolver::for($request);
            }
        });
    })->create();
