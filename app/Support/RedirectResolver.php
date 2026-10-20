<?php

namespace App\Support;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Looks up a managed redirect for a request that would otherwise 404. */
class RedirectResolver
{
    public static function for(Request $request): ?Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return null;
        }

        try {
            $redirect = Redirect::where('from_path', Redirect::normalize($request->getPathInfo()))->where('is_active', true)->first();
        } catch (\Throwable) {
            return null;
        }

        if (! $redirect) {
            return null;
        }

        $redirect->increment('hits', 1, ['last_hit_at' => now()]);

        if ($redirect->status_code === 410) {
            return response()->view('errors.410', [], 410);
        }

        $target = str_starts_with($redirect->to_path, 'http') ? $redirect->to_path : url($redirect->to_path);
        if ($query = $request->getQueryString()) {
            $target .= (str_contains($target, '?') ? '&' : '?').$query;
        }

        return redirect()->to($target, $redirect->status_code);
    }
}
