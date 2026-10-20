<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tokens of deactivated staff stop working immediately. */
class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user && ! $user->is_active, 403, 'This account is disabled.');

        return $next($request);
    }
}
