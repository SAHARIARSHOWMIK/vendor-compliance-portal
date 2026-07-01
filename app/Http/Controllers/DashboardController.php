<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Single '/dashboard' entry point that every authenticated user lands on
 * after login. Routes to a role-specific view rather than having five
 * separate post-login redirect targets scattered across the auth flow -
 * keeps the "where do I go after I log in" decision in one place.
 */
class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        return match ($user->role) {
            RoleName::SuperAdmin, RoleName::ComplianceAdmin => redirect()->route('admin.dashboard'),
            RoleName::Reviewer => redirect()->route('reviewer.queue'),
            RoleName::VendorUser => redirect()->route('vendor-portal.dashboard'),
            RoleName::Auditor => redirect()->route('auditor.dashboard'),
        };
    }
}
