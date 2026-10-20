<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/** Guards /docs/api (Scramble): logged-in users passing the `viewApiDocs` gate only. */
class AuthorizeApiDocs
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return Route::has('filament.admin.auth.login') && ! $request->expectsJson()
                ? redirect()->guest(route('filament.admin.auth.login'))
                : abort(401);
        }

        abort_unless(Gate::allows('viewApiDocs'), 403);

        return $next($request);
    }
}
