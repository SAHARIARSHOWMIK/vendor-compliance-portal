<?php

namespace App\Services;

use App\Mail\DocumentReviewedMail;
use App\Mail\ExpiryWarningMail;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Support\Facades\Mail;

/**
 * In-app and email notification service.
 *
 * All workflows create in-app Notification rows. Review decisions and
 * expiry warnings also dispatch email. In demo mode MAIL_MAILER=log writes
 * email output to storage/logs/laravel.log instead of sending externally.
 */
class NotificationService
{
    // -----------------------------------------------------------------
    // Review decision notifications
    // -----------------------------------------------------------------

    public function notifyVendorUsersOfReview(VendorDocument $document, Review $review): void
    {
        $vendor = $document->vendor;
        $users  = $vendor->vendorUsers()->with('user')->get()->pluck('user')->filter();

        $title   = $this->reviewDecisionTitle($review->decision, $document->documentType->name);
        $message = $review->comment
            ? "Reviewer note: {$review->comment}"
            : "Your document has been reviewed.";

        $type = match ($review->decision) {
            'approved'             => 'success',
            'rejected',
            'correction_requested' => 'action_required',
            'need_more_info'       => 'warning',
            'escalated'            => 'info',
            default                => 'info',
        };

        $actionUrl = route('vendor-portal.checklist');

        foreach ($users as $user) {
            // In-app notification
            Notification::create([
                'user_id'            => $user->id,
                'title'              => $title,
                'message'            => $message,
                'type'               => $type,
                'vendor_id'          => $vendor->id,
                'vendor_document_id' => $document->id,
                'action_url'         => $actionUrl,
                'is_read'            => false,
            ]);

            // Email notification (MAIL_MAILER=log in demo)
            Mail::to($user->email)->send(
                new DocumentReviewedMail($document, $review)
            );
        }
    }

    // -----------------------------------------------------------------
    // Upload notifications (to reviewers)
    // -----------------------------------------------------------------

    public function notifyReviewersOfUpload(VendorDocument $document): void
    {
        $reviewers = User::whereIn('role', ['reviewer', 'compliance_admin', 'super_admin'])->get();
        $vendor    = $document->vendor;
        $docName   = $document->documentType->name;
        $title     = "New document uploaded: {$docName}";
        $message   = "{$vendor->name} uploaded {$docName} (v{$document->version_number}).";
        $actionUrl = route('reviewer.documents.show', $document);

        foreach ($reviewers as $user) {
            Notification::create([
                'user_id'            => $user->id,
                'title'              => $title,
                'message'            => $message,
                'type'               => 'info',
                'vendor_id'          => $vendor->id,
                'vendor_document_id' => $document->id,
                'action_url'         => $actionUrl,
                'is_read'            => false,
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Expiry warnings (from the nightly CheckDocumentExpiry command)
    // -----------------------------------------------------------------

    public function notifyExpiryWarning(VendorDocument $document, int $daysLeft): void
    {
        $vendor  = $document->vendor;
        $docName = $document->documentType->name;
        $type    = $daysLeft <= 0 ? 'urgent' : ($daysLeft <= 7 ? 'urgent' : ($daysLeft <= 30 ? 'warning' : 'info'));
        $title   = $daysLeft <= 0
            ? "{$docName} has expired"
            : "{$docName} expiring in {$daysLeft} day" . ($daysLeft === 1 ? '' : 's');
        $message = $daysLeft <= 0
            ? "Your document has expired. Please upload a renewed copy immediately."
            : "Please renew {$docName} before it expires.";

        $recipients = $vendor->vendorUsers()->with('user')->get()->pluck('user')->filter();

        foreach ($recipients as $user) {
            Notification::create([
                'user_id'            => $user->id,
                'title'              => $title,
                'message'            => $message,
                'type'               => $type,
                'vendor_id'          => $vendor->id,
                'vendor_document_id' => $document->id,
                'action_url'         => route('vendor-portal.checklist'),
                'is_read'            => false,
            ]);

            Mail::to($user->email)->send(
                new ExpiryWarningMail($document, $daysLeft)
            );
        }
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function reviewDecisionTitle(string $decision, string $docName): string
    {
        return match ($decision) {
            'approved'             => "{$docName} approved",
            'rejected'             => "{$docName} rejected — action required",
            'correction_requested' => "Correction requested on {$docName}",
            'need_more_info'       => "More information needed for {$docName}",
            'escalated'            => "{$docName} has been escalated",
            default                => "{$docName} reviewed",
        };
    }
}
