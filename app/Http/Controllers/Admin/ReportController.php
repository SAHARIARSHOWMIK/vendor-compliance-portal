<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly AuditService  $auditService,
    ) {}

    public function index(): View
    {
        $this->authorize('export', Vendor::class);
        return view('admin.reports.index');
    }

    // -----------------------------------------------------------------
    // Report pages (HTML)
    // -----------------------------------------------------------------

    public function complianceSummary(Request $request): View
    {
        $this->authorize('export', Vendor::class);
        $vendors = $this->reportService->complianceSummaryData($request->only(['category', 'risk_level', 'compliance_status']));
        return view('admin.reports.compliance-summary', compact('vendors'));
    }

    public function missingDocuments(): View
    {
        $this->authorize('export', Vendor::class);
        $rows = $this->reportService->missingDocumentsData();
        return view('admin.reports.missing-documents', compact('rows'));
    }

    public function expiringDocuments(Request $request): View
    {
        $this->authorize('export', Vendor::class);
        $withinDays = (int) $request->get('within_days', 60);
        $documents  = $this->reportService->expiringDocumentsData($withinDays);
        return view('admin.reports.expiring-documents', compact('documents', 'withinDays'));
    }

    public function rejectedDocuments(): View
    {
        $this->authorize('export', Vendor::class);
        $documents = $this->reportService->rejectedDocumentsData();
        return view('admin.reports.rejected-documents', compact('documents'));
    }

    public function vendorOnboarding(): View
    {
        $this->authorize('export', Vendor::class);
        $vendors = $this->reportService->vendorOnboardingData();
        return view('admin.reports.vendor-onboarding', compact('vendors'));
    }

    public function reviewerWorkload(): View
    {
        $this->authorize('export', Vendor::class);
        $users = $this->reportService->reviewerWorkloadData();
        return view('admin.reports.reviewer-workload', compact('users'));
    }

    public function auditLog(Request $request): View
    {
        $this->authorize('export', Vendor::class);
        $logs = \App\Models\AuditLog::with(['vendor'])
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
            ->when($request->filled('event_type'), fn ($q) => $q->where('event_type', $request->event_type))
            ->when($request->filled('from_date'), fn ($q) => $q->where('occurred_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->where('occurred_at', '<=', $request->to_date . ' 23:59:59'))
            ->orderBy('occurred_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $vendors    = Vendor::orderBy('name')->get(['id', 'name']);
        $eventTypes = \App\Models\AuditLog::distinct()->orderBy('event_type')->pluck('event_type');

        return view('admin.reports.audit-log', compact('logs', 'vendors', 'eventTypes'));
    }

    // -----------------------------------------------------------------
    // CSV exports
    // -----------------------------------------------------------------

    public function exportComplianceSummary(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('compliance_summary', $request->user());
        return $this->reportService->complianceSummaryCsv($request->only(['category', 'risk_level', 'compliance_status']));
    }

    public function exportMissingDocuments(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('missing_documents', $request->user());
        return $this->reportService->missingDocumentsCsv();
    }

    public function exportExpiringDocuments(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('expiring_documents', $request->user());
        return $this->reportService->expiringDocumentsCsv((int) $request->get('within_days', 60));
    }

    public function exportRejectedDocuments(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('rejected_documents', $request->user());
        return $this->reportService->rejectedDocumentsCsv();
    }

    public function exportVendorOnboarding(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('vendor_onboarding', $request->user());
        return $this->reportService->vendorOnboardingCsv();
    }

    public function exportReviewerWorkload(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('reviewer_workload', $request->user());
        return $this->reportService->reviewerWorkloadCsv();
    }

    public function exportAuditLog(Request $request): StreamedResponse
    {
        $this->authorize('export', Vendor::class);
        $this->logExport('audit_log', $request->user());
        return $this->reportService->auditLogCsv($request->only(['vendor_id', 'event_type', 'from_date', 'to_date']));
    }

    private function logExport(string $reportType, $user): void
    {
        $this->auditService->log(
            eventType:   'report_exported',
            description: "Report '{$reportType}' exported as CSV by {$user->name}.",
            actor:       $user,
            request:     request(),
        );
    }
}
