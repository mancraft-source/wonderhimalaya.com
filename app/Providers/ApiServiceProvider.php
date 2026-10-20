<?php

namespace App\Providers;

use App\Models\User;
use App\Payments\PaymentManager;
use App\Support\Permissions;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/** REST API v1, API docs and payments wiring. */
class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class);
    }

    public function boot(): void
    {
        Gate::define('viewApiDocs', fn (?User $user) => $user !== null
            && $user->is_active
            && ($user->isAdmin() || $user->can(Permissions::API)));

        // Public quote / payment pages: UUID links + modest limits against enumeration and abuse.
        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(30)->by('pay:'.$request->ip()));

        Scramble::configure()
            ->routes(fn (Route $route) => Str::startsWith($route->uri, 'api/v1/'));
    }
}
