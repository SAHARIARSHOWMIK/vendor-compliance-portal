<?php

namespace App\Http\Controllers\VendorPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorPortalDashboardController extends Controller
{
    /**
     * The vendor portal "dashboard" is the document checklist page.
     * Redirect there directly so /vendor-portal/dashboard and
     * /vendor-portal/checklist both land on the same experience.
     */
    public function index(Request $request): RedirectResponse
    {
        if (! $request->user()->vendor()) {
            return redirect()->route('vendor-portal.checklist');
        }

        return redirect()->route('vendor-portal.checklist');
    }
}
