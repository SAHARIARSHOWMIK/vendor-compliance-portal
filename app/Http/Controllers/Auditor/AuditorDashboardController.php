<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only views for the Auditor role. Every method here only reads
 * data — no controller action in this class ever mutates a model, which
 * mirrors the "cannot edit anything" rule enforced by VendorPolicy /
 * VendorDocumentPolicy / ReviewPolicy for the Auditor role.
 */
class AuditorDashboardController extends Controller
{
    public function index(): View
    {
        $totalVendors   = Vendor::whereNotIn('status', ['archived'])->count();
        $fullyCompliant = Vendor::where('compliance_status', 'fully_compliant')->count();
        $nonCompliant   = Vendor::where('compliance_status', 'non_compliant')->count();
        $recentLogs     = AuditLog::with('vendor')->orderBy('occurred_at', 'desc')->limit(15)->get();

        return view('auditor.dashboard', compact('totalVendors', 'fullyCompliant', 'nonCompliant', 'recentLogs'));
    }

    public function vendors(Request $request): View
    {
        $query = Vendor::with(['latestComplianceCheck', 'assignedReviewer'])->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->paginate(25)->withQueryString();

        return view('auditor.vendors', compact('vendors'));
    }

    public function vendorDetail(Vendor $vendor): View
    {
        $vendor->load([
            'documents.documentType',
            'documents.reviews.reviewer',
            'auditLogs' => fn ($q) => $q->orderBy('occurred_at', 'desc'),
        ]);

        return view('auditor.vendor-detail', compact('vendor'));
    }
}
