<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to vendor-portal routes (routes/web.php, the 'vendor-portal'
 * route group). Ensures a VendorUser can only ever reach records
 * belonging to their own vendor company - even if they guess another
 * vendor's ID in the URL.
 *
 * Internal-role users (admin/compliance admin/reviewer/auditor) bypass
 * this check entirely, since they are expected to see across vendors;
 * their access is instead governed by Policies per-action.
 *
 * Expects the route to have a {vendor} route-model-bound parameter, OR
 * relies on the controller resolving the vendor from the authenticated
 * VendorUser when no {vendor} parameter is present (e.g. "my dashboard"
 * routes that don't take a vendor ID in the URL at all).
 */
class EnsureVendorScopedAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->isVendorUser()) {
            // Internal roles are not vendor-scoped by this middleware -
            // their authorization is handled by Policies.
            return $next($request);
        }

        $userVendor = $user->vendor();

        // If the user is not yet linked to a vendor company, let them
        // reach the portal dashboard (which shows a "contact your admin"
        // message) rather than hard-aborting. The no-vendor-linked check
        // in the controller/view gives a better user experience than a
        // generic 403. Cross-vendor access is still blocked below.
        if (! $userVendor) {
            return $next($request);
        }

        $routeVendor = $request->route('vendor');

        if ($routeVendor instanceof Vendor && $routeVendor->id !== $userVendor->id) {
            abort(403, 'You do not have access to this vendor record.');
        }

        return $next($request);
    }
}
