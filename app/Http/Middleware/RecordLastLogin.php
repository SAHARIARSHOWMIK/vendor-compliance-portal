<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Updates users.last_login_at once per session, the first time an
 * authenticated request is seen after login - cheap to run on every web
 * request since it only writes when the cached session flag is absent.
 */
class RecordLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $request->session()->get('last_login_recorded')) {
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->put('last_login_recorded', true);
        }

        return $next($request);
    }
}
