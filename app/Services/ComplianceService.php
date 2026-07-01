<?php

namespace App\Services;

use App\Models\ComplianceCheck;
use App\Models\Vendor;

class ComplianceService
{
    private int $urgentDays;

    public function __construct()
    {
        $this->urgentDays = (int) env('COMPLIANCE_EXPIRY_URGENT_DAYS', 7);
    }

    public function recalculate(Vendor $vendor): ComplianceCheck
    {
        $requiredTypes = $vendor->requiredDocumentTypes();
        $documents     = $vendor->documents()->with('documentType')->get()->keyBy('document_type_id');

        $totalRequired = $requiredTypes->count();
        $totalApproved = $totalMissing = $totalRejected = 0;
        $totalExpired  = $totalExpiring = $totalPending = $totalUploaded = 0;

        foreach ($requiredTypes as $docType) {
            $doc = $documents->get($docType->id);
            if (! $doc) { $totalMissing++; continue; }
            $totalUploaded++;
            if ($doc->isExpired())               $totalExpired++;
            elseif ($doc->isExpiringSoon($this->urgentDays)) $totalExpiring++;
            match ($doc->status) {
                'approved'             => $totalApproved++,
                'rejected',
                'correction_requested' => $totalRejected++,
                'under_review','uploaded','reuploaded' => $totalPending++,
                default => null,
            };
        }

        $overallStatus = $this->deriveStatus(
            $vendor, $totalRequired, $totalApproved,
            $totalMissing, $totalRejected, $totalExpired, $totalExpiring, $totalPending
        );
        $score = $this->calculateScore($totalRequired, $totalApproved, $totalMissing, $totalRejected, $totalExpired, $totalExpiring);

        $check = ComplianceCheck::create([
            'vendor_id'            => $vendor->id,
            'total_required'       => $totalRequired,
            'total_uploaded'       => $totalUploaded,
            'total_approved'       => $totalApproved,
            'total_missing'        => $totalMissing,
            'total_rejected'       => $totalRejected,
            'total_expired'        => $totalExpired,
            'total_expiring_soon'  => $totalExpiring,
            'total_pending_review' => $totalPending,
            'compliance_score'     => $score,
            'overall_status'       => $overallStatus,
            'checked_at'           => now(),
        ]);

        $vendor->update([
            'compliance_status' => $overallStatus,
            'compliance_score'  => $score,
            'status'            => $this->deriveVendorStatus($vendor, $overallStatus),
        ]);

        return $check;
    }

    private function deriveStatus(Vendor $vendor, int $required, int $approved, int $missing, int $rejected, int $expired, int $expiring, int $pending): string
    {
        if ($vendor->isSuspended()) return 'suspended';
        if ($required === 0)        return 'fully_compliant';
        if ($expired > 0)           return 'non_compliant';
        if ($expiring > 0 && $missing === 0 && $rejected === 0 && $expired === 0) return 'expiring_soon';
        if ($missing === 0 && $rejected === 0 && $expired === 0 && $pending === 0) return 'fully_compliant';
        if ($rejected > 0) return 'correction_required';
        if ($missing > 0)  return 'documents_missing';
        if ($pending > 0)  return 'under_review';
        if ($approved > 0 && $approved < $required) return 'partially_compliant';
        return 'documents_missing';
    }

    private function deriveVendorStatus(Vendor $vendor, string $complianceStatus): string
    {
        if (in_array($vendor->status, ['suspended', 'archived'], true)) return $vendor->status;
        return match ($complianceStatus) {
            'fully_compliant'    => 'fully_compliant',
            'expiring_soon'      => 'expiring_soon',
            'non_compliant'      => 'non_compliant',
            'correction_required' => 'correction_required',
            'under_review'       => 'under_review',
            'documents_missing'  => 'documents_pending',
            'partially_compliant' => 'partially_approved',
            default              => $vendor->status,
        };
    }

    private function calculateScore(int $required, int $approved, int $missing, int $rejected, int $expired, int $expiring): int
    {
        if ($required === 0) return 100;
        $base  = ($approved / $required) * 100;
        $base -= ($expired  / $required) * 40;
        $base -= ($rejected / $required) * 20;
        $base -= ($expiring / $required) * 10;
        return max(0, min(100, (int) round($base)));
    }
}
