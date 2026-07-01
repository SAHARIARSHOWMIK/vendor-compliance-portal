<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorDocument;

class ReviewService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function approve(VendorDocument $document, string $comment, User $reviewer): Review
    {
        return $this->decide($document, 'approved', 'approved', $comment, $reviewer, 'document_approved', "Document '{$document->documentType->name}' approved by {$reviewer->name}.");
    }

    public function reject(VendorDocument $document, string $comment, User $reviewer): Review
    {
        return $this->decide($document, 'rejected', 'rejected', $comment, $reviewer, 'document_rejected', "Document '{$document->documentType->name}' rejected by {$reviewer->name}.");
    }

    public function requestCorrection(VendorDocument $document, string $comment, User $reviewer): Review
    {
        return $this->decide($document, 'correction_requested', 'correction_requested', $comment, $reviewer, 'correction_requested', "Correction requested on '{$document->documentType->name}' by {$reviewer->name}.");
    }

    public function requestMoreInfo(VendorDocument $document, string $comment, User $reviewer): Review
    {
        return $this->decide($document, 'need_more_info', 'under_review', $comment, $reviewer, 'more_info_requested', "More information requested on '{$document->documentType->name}' by {$reviewer->name}.");
    }

    public function escalate(VendorDocument $document, string $comment, User $reviewer): Review
    {
        return $this->decide($document, 'escalated', 'under_review', $comment, $reviewer, 'document_escalated', "Document '{$document->documentType->name}' escalated by {$reviewer->name}.");
    }

    private function decide(VendorDocument $document, string $decision, string $newDocumentStatus, string $comment, User $reviewer, string $auditEvent, string $auditDesc): Review
    {
        $oldStatus = $document->status;

        $review = Review::create([
            'vendor_document_id' => $document->id,
            'vendor_id'          => $document->vendor_id,
            'reviewer_id'        => $reviewer->id,
            'decision'           => $decision,
            'comment'            => $comment ?: null,
            'document_version'   => $document->version_number,
            'reviewed_at'        => now(),
        ]);

        $document->update([
            'status'         => $newDocumentStatus,
            'reviewed_by'    => $reviewer->id,
            'reviewed_at'    => now(),
            'review_comment' => $comment ?: null,
        ]);

        AuditLog::create([
            'actor_id'           => $reviewer->id,
            'actor_name'         => $reviewer->name,
            'actor_role'         => $reviewer->role->value,
            'vendor_id'          => $document->vendor_id,
            'vendor_name'        => $document->vendor->name,
            'vendor_document_id' => $document->id,
            'event_type'         => $auditEvent,
            'description'        => $auditDesc,
            'old_values'         => ['status' => $oldStatus],
            'new_values'         => ['status' => $newDocumentStatus, 'decision' => $decision, 'comment' => $comment],
            'occurred_at'        => now(),
        ]);

        $this->notificationService->notifyVendorUsersOfReview($document, $review);
        app(ComplianceService::class)->recalculate($document->vendor);

        return $review;
    }
}
