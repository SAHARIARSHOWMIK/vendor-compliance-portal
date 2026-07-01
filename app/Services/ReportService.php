<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates the 7 report types from spec section 10/page 9.
 *
 * Every report is available as:
 *   - A PHP data array/collection (for the HTML report pages)
 *   - A CSV StreamedResponse (for the export button)
 *
 * CSV streaming never loads all rows into memory at once — it uses a
 * generator pattern via PHP's output buffering + fputcsv, which handles
 * large datasets without hitting memory limits.
 */
class ReportService
{
    // -----------------------------------------------------------------
    // 1. Compliance summary report
    // -----------------------------------------------------------------

    public function complianceSummaryData(array $filters = []): Collection
    {
        $query = Vendor::with(['latestComplianceCheck', 'assignedReviewer'])
            ->whereNotIn('status', ['archived'])
            ->orderBy('name');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }
        if (! empty($filters['compliance_status'])) {
            $query->where('compliance_status', $filters['compliance_status']);
        }

        return $query->get();
    }

    public function complianceSummaryCsv(array $filters = []): StreamedResponse
    {
        $vendors = $this->complianceSummaryData($filters);

        return $this->streamCsv('compliance-summary', [
            'Vendor Name', 'Category', 'Risk Level', 'Compliance Status',
            'Compliance Score', 'Vendor Status', 'Assigned Reviewer',
        ], $vendors->map(fn ($v) => [
            $v->name,
            ucwords(str_replace('_', ' ', $v->category)),
            ucfirst($v->risk_level),
            $v->compliance_status ?? '-',
            $v->compliance_score . '%',
            $v->status,
            $v->assignedReviewer?->name ?? '-',
        ])->all());
    }

    // -----------------------------------------------------------------
    // 2. Missing documents report
    // -----------------------------------------------------------------

    public function missingDocumentsData(array $filters = []): array
    {
        $vendors = Vendor::with(['documents.documentType'])
            ->whereNotIn('status', ['archived', 'suspended'])
            ->get();

        $rows = [];
        foreach ($vendors as $vendor) {
            $required = $vendor->requiredDocumentTypes();
            $uploaded = $vendor->documents->pluck('document_type_id')->toArray();

            foreach ($required as $docType) {
                if (! in_array($docType->id, $uploaded, true)) {
                    $rows[] = [
                        'vendor'        => $vendor,
                        'document_type' => $docType,
                    ];
                }
            }
        }

        return $rows;
    }

    public function missingDocumentsCsv(): StreamedResponse
    {
        $rows = $this->missingDocumentsData();

        return $this->streamCsv('missing-documents', [
            'Vendor Name', 'Category', 'Risk Level', 'Missing Document', 'Vendor Status',
        ], array_map(fn ($row) => [
            $row['vendor']->name,
            ucwords(str_replace('_', ' ', $row['vendor']->category)),
            ucfirst($row['vendor']->risk_level),
            $row['document_type']->name,
            $row['vendor']->status,
        ], $rows));
    }

    // -----------------------------------------------------------------
    // 3. Expiring documents report
    // -----------------------------------------------------------------

    public function expiringDocumentsData(int $withinDays = 60): Collection
    {
        return VendorDocument::with(['vendor', 'documentType'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now()->toDateString())
            ->where('expiry_date', '<=', now()->addDays($withinDays)->toDateString())
            ->whereIn('status', ['approved', 'expiring_soon'])
            ->orderBy('expiry_date')
            ->get();
    }

    public function expiringDocumentsCsv(int $withinDays = 60): StreamedResponse
    {
        $docs = $this->expiringDocumentsData($withinDays);

        return $this->streamCsv('expiring-documents', [
            'Vendor Name', 'Document Type', 'Expiry Date', 'Days Remaining',
            'Risk Level', 'Vendor Status',
        ], $docs->map(fn ($d) => [
            $d->vendor->name,
            $d->documentType->name,
            $d->expiry_date->format('d M Y'),
            $d->daysUntilExpiry(),
            ucfirst($d->vendor->risk_level),
            $d->vendor->status,
        ])->all());
    }

    // -----------------------------------------------------------------
    // 4. Rejected documents report
    // -----------------------------------------------------------------

    public function rejectedDocumentsData(): Collection
    {
        return VendorDocument::with(['vendor', 'documentType', 'reviewer'])
            ->whereIn('status', ['rejected', 'correction_requested'])
            ->orderBy('reviewed_at', 'desc')
            ->get();
    }

    public function rejectedDocumentsCsv(): StreamedResponse
    {
        $docs = $this->rejectedDocumentsData();

        return $this->streamCsv('rejected-documents', [
            'Vendor Name', 'Document Type', 'Status', 'Reviewer',
            'Review Date', 'Comment',
        ], $docs->map(fn ($d) => [
            $d->vendor->name,
            $d->documentType->name,
            ucwords(str_replace('_', ' ', $d->status)),
            $d->reviewer?->name ?? '-',
            $d->reviewed_at?->format('d M Y') ?? '-',
            $d->review_comment ?? '-',
        ])->all());
    }

    // -----------------------------------------------------------------
    // 5. Vendor onboarding status report
    // -----------------------------------------------------------------

    public function vendorOnboardingData(): Collection
    {
        return Vendor::with(['vendorUsers.user', 'latestComplianceCheck'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function vendorOnboardingCsv(): StreamedResponse
    {
        $vendors = $this->vendorOnboardingData();

        return $this->streamCsv('vendor-onboarding-status', [
            'Vendor Name', 'Category', 'Risk Level', 'Onboarding Status',
            'Compliance Status', 'Score', 'Invited At', 'Registered At', 'Created At',
        ], $vendors->map(fn ($v) => [
            $v->name,
            ucwords(str_replace('_', ' ', $v->category)),
            ucfirst($v->risk_level),
            $v->status,
            $v->compliance_status ?? '-',
            $v->compliance_score . '%',
            $v->invited_at?->format('d M Y') ?? '-',
            $v->registered_at?->format('d M Y') ?? '-',
            $v->created_at->format('d M Y'),
        ])->all());
    }

    // -----------------------------------------------------------------
    // 6. Reviewer workload report
    // -----------------------------------------------------------------

    public function reviewerWorkloadData(): Collection
    {
        // vendors_assigned requires a separate count since it's on the
        // vendors table (assigned_reviewer_id), not a hasMany on User.
        $users = User::whereIn('role', ['reviewer', 'compliance_admin', 'super_admin'])
            ->withCount([
                'reviewsGiven as total_reviews',
                'reviewsGiven as reviews_this_month' => fn ($q) => $q->where('reviewed_at', '>=', now()->startOfMonth()),
            ])
            ->orderBy('name')
            ->get();

        // Attach vendor count per reviewer via a grouped query
        $vendorCounts = \App\Models\Vendor::selectRaw('assigned_reviewer_id, COUNT(*) as cnt')
            ->whereNotNull('assigned_reviewer_id')
            ->groupBy('assigned_reviewer_id')
            ->pluck('cnt', 'assigned_reviewer_id');

        $users->each(function ($user) use ($vendorCounts) {
            $user->vendors_assigned = $vendorCounts->get($user->id, 0);
        });

        return $users;
    }

    public function reviewerWorkloadCsv(): StreamedResponse
    {
        $users = $this->reviewerWorkloadData();

        return $this->streamCsv('reviewer-workload', [
            'Reviewer Name', 'Role', 'Total Reviews', 'Reviews This Month', 'Vendors Assigned',
        ], $users->map(fn ($u) => [
            $u->name,
            $u->role->label(),
            $u->total_reviews ?? 0,
            $u->reviews_this_month ?? 0,
            $u->vendors_assigned ?? 0,
        ])->all());
    }

    // -----------------------------------------------------------------
    // 7. Audit log export
    // -----------------------------------------------------------------

    public function auditLogCsv(array $filters = []): StreamedResponse
    {
        $query = AuditLog::with(['actor', 'vendor'])
            ->orderBy('occurred_at', 'desc');

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }
        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
        if (! empty($filters['from_date'])) {
            $query->where('occurred_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->where('occurred_at', '<=', $filters['to_date'] . ' 23:59:59');
        }

        // Use lazy() to avoid loading all audit logs into memory at once
        $logs = $query->lazy(500);

        return $this->streamCsvFromGenerator('audit-log', [
            'Timestamp', 'Actor', 'Actor Role', 'Vendor', 'Event Type', 'Description',
        ], (function () use ($logs) {
            foreach ($logs as $log) {
                yield [
                    $log->occurred_at->format('d M Y H:i:s'),
                    $log->actor_name ?? 'System',
                    $log->actor_role ?? '-',
                    $log->vendor_name ?? '-',
                    $log->event_type,
                    $log->description,
                ];
            }
        })());
    }

    // -----------------------------------------------------------------
    // CSV streaming helpers
    // -----------------------------------------------------------------

    private function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return Response::streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename . '-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}-" . now()->format('Y-m-d') . ".csv\"",
        ]);
    }

    private function streamCsvFromGenerator(string $filename, array $headers, \Generator $rows): StreamedResponse
    {
        return Response::streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename . '-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}-" . now()->format('Y-m-d') . ".csv\"",
        ]);
    }
}
