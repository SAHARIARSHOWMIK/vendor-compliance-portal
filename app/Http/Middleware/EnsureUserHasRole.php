<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes: ->middleware('role:super_admin,compliance_admin')
 *
 * Returns 403 if the authenticated user's role is not in the allowed
 * list. This is route-level defense; Policies (app/Policies) provide the
 * second, more granular layer (e.g. "reviewer can only act on documents
 * assigned to them"), so a route passing this middleware does not by
 * itself guarantee the action is allowed on a *specific* record.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = array_map(fn (string $r) => RoleName::from($r), $roles);

        if (! $user->hasAnyRole($allowed)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
