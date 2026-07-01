<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplianceCheck;
use App\Models\Ticket;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // All 9 metrics from spec page 1 (Admin Dashboard)
        $totalVendors        = Vendor::whereNotIn('status', ['archived'])->count();
        $fullyCompliant      = Vendor::where('compliance_status', 'fully_compliant')->count();
        $nonCompliant        = Vendor::where('compliance_status', 'non_compliant')->count();
        $underReview         = Vendor::where('compliance_status', 'under_review')->count();
        $pendingReviewDocs   = VendorDocument::whereIn('status', ['uploaded', 'reuploaded', 'under_review'])->count();
        $expiringSoonDocs    = VendorDocument::where('status', 'expiring_soon')
            ->orWhere(function ($q) {
                $q->whereNotNull('expiry_date')
                  ->where('expiry_date', '<=', now()->addDays(60))
                  ->where('expiry_date', '>=', now())
                  ->whereIn('status', ['approved', 'expiring_soon']);
            })->count();
        $rejectedDocs        = VendorDocument::whereIn('status', ['rejected', 'correction_requested'])->count();
        $correctionRequests  = VendorDocument::where('status', 'correction_requested')->count();
        $highRiskVendors     = Vendor::where('risk_level', 'high')
            ->whereNotIn('status', ['archived'])->count();

        // Recent compliance activity for the dashboard timeline
        $recentActivity = \App\Models\AuditLog::with(['vendor'])
            ->orderBy('occurred_at', 'desc')
            ->limit(10)
            ->get();

        // Compliance score distribution for the bar chart
        $scoreDistribution = [
            '0-25'   => Vendor::whereBetween('compliance_score', [0, 25])->count(),
            '26-50'  => Vendor::whereBetween('compliance_score', [26, 50])->count(),
            '51-75'  => Vendor::whereBetween('compliance_score', [51, 75])->count(),
            '76-99'  => Vendor::whereBetween('compliance_score', [76, 99])->count(),
            '100'    => Vendor::where('compliance_score', 100)->count(),
        ];

        return view('admin.dashboard', compact(
            'totalVendors', 'fullyCompliant', 'nonCompliant', 'underReview',
            'pendingReviewDocs', 'expiringSoonDocs', 'rejectedDocs',
            'correctionRequests', 'highRiskVendors',
            'recentActivity', 'scoreDistribution',
        ));
    }
}
