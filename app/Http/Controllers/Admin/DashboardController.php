<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Review;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeVendors = Vendor::query()->whereNotIn('status', ['archived']);

        $totalVendors       = (clone $activeVendors)->count();
        $fullyCompliant     = Vendor::where('compliance_status', 'fully_compliant')->count();
        $nonCompliant       = Vendor::where('compliance_status', 'non_compliant')->count();
        $underReview        = Vendor::where('compliance_status', 'under_review')->count();
        $pendingReviewDocs  = VendorDocument::whereIn('status', ['uploaded', 'reuploaded', 'under_review'])->count();
        $rejectedDocs       = VendorDocument::whereIn('status', ['rejected', 'correction_requested'])->count();
        $correctionRequests = VendorDocument::where('status', 'correction_requested')->count();
        $highRiskVendors    = (clone $activeVendors)->where('risk_level', 'high')->count();

        $expiringSoonDocs = VendorDocument::query()
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
            ->whereIn('status', ['approved', 'expiring_soon'])
            ->count();

        $averageScore = round((float) ((clone $activeVendors)->avg('compliance_score') ?? 0));
        $complianceRate = $totalVendors > 0 ? round(($fullyCompliant / $totalVendors) * 100) : 0;
        $documentsReviewedThisMonth = Review::where('reviewed_at', '>=', now()->startOfMonth())->count();
        $overdueReviewDocs = VendorDocument::query()
            ->whereIn('status', ['uploaded', 'reuploaded', 'under_review'])
            ->where('uploaded_at', '<=', now()->subDays(3))
            ->count();

        $recentActivity = AuditLog::with(['vendor', 'actor'])
            ->orderByDesc('occurred_at')
            ->limit(10)
            ->get();

        $scoreDistribution = [
            '0–25'  => Vendor::whereBetween('compliance_score', [0, 25])->count(),
            '26–50' => Vendor::whereBetween('compliance_score', [26, 50])->count(),
            '51–75' => Vendor::whereBetween('compliance_score', [51, 75])->count(),
            '76–99' => Vendor::whereBetween('compliance_score', [76, 99])->count(),
            '100'   => Vendor::where('compliance_score', 100)->count(),
        ];

        $statusPipeline = collect([
            'Onboarding' => ['draft', 'invited', 'registered'],
            'Documents pending' => ['documents_pending'],
            'Under review' => ['under_review', 'partially_approved'],
            'Correction required' => ['correction_required', 'non_compliant'],
            'Compliant' => ['fully_compliant'],
            'Expiring' => ['expiring_soon'],
        ])->mapWithKeys(fn (array $statuses, string $label) => [
            $label => Vendor::whereIn('status', $statuses)->count(),
        ]);

        $categoryDistribution = Vendor::query()
            ->selectRaw('category, COUNT(*) as total')
            ->whereNotIn('status', ['archived'])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($row) => [ucwords(str_replace('_', ' ', $row->category)) => (int) $row->total]);

        $priorityVendors = Vendor::query()
            ->with('assignedReviewer')
            ->whereNotIn('status', ['archived'])
            ->where(function ($query): void {
                $query->where('risk_level', 'high')
                    ->orWhere('compliance_score', '<', 60)
                    ->orWhereIn('compliance_status', ['non_compliant', 'correction_required', 'documents_missing']);
            })
            ->orderByRaw("CASE risk_level WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderBy('compliance_score')
            ->limit(6)
            ->get();

        $upcomingExpiries = VendorDocument::query()
            ->with(['vendor', 'documentType'])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
            ->whereIn('status', ['approved', 'expiring_soon'])
            ->orderBy('expiry_date')
            ->limit(6)
            ->get();

        $recentReviews = Review::query()
            ->with(['reviewer', 'vendorDocument.vendor', 'vendorDocument.documentType'])
            ->orderByDesc('reviewed_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalVendors', 'fullyCompliant', 'nonCompliant', 'underReview',
            'pendingReviewDocs', 'expiringSoonDocs', 'rejectedDocs',
            'correctionRequests', 'highRiskVendors', 'recentActivity',
            'scoreDistribution', 'averageScore', 'complianceRate',
            'documentsReviewedThisMonth', 'overdueReviewDocs', 'statusPipeline',
            'categoryDistribution', 'priorityVendors', 'upcomingExpiries',
            'recentReviews',
        ));
    }
}
