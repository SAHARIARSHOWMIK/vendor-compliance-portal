<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\VendorDocument;
use App\Models\Vendor;

class VendorDocumentPolicy
{
    /**
     * Can a user see the document list for a vendor?
     * Vendor users can only see their own vendor's documents.
     */
    public function viewAny(User $user, Vendor $vendor): bool
    {
        if ($user->isVendorUser()) {
            return $user->vendor()?->id === $vendor->id;
        }
        return true; // all internal roles
    }

    /**
     * Can a user view/download a specific document?
     */
    public function view(User $user, VendorDocument $document): bool
    {
        if ($user->isVendorUser()) {
            return $user->vendor()?->id === $document->vendor_id;
        }
        return true;
    }

    /**
     * Can a user upload a document for a vendor?
     * - Admin roles: yes (they may upload on behalf of a vendor)
     * - Vendor user: only for their own vendor, and vendor must be active
     * - Auditors: never
     */
    public function upload(User $user, Vendor $vendor): bool
    {
        if ($user->isAuditor()) {
            return false;
        }

        if ($user->isVendorUser()) {
            return $user->vendor()?->id === $vendor->id
                && $vendor->canUploadDocuments();
        }

        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin]);
    }

    /**
     * Can a user replace a rejected/correction-requested document?
     * Same rules as upload, but also checks the current document status
     * allows replacement.
     */
    public function replace(User $user, VendorDocument $document): bool
    {
        $vendor = $document->vendor;

        if (! $this->upload($user, $vendor)) {
            return false;
        }

        // Vendor users can only replace documents that were rejected or
        // correction-requested — not approved ones.
        if ($user->isVendorUser()) {
            return in_array($document->status, ['rejected', 'correction_requested', 'expired'], true);
        }

        return true;
    }

    /**
     * Can a user download a document version from version history?
     * Same as view.
     */
    public function downloadVersion(User $user, VendorDocument $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Allow internal review roles to make decisions on reviewable documents.
     */
    public function decide(User $user, VendorDocument $document): bool
    {
        if ($user->isVendorUser() || $user->isAuditor()) {
            return false;
        }

        if (! $user->hasAnyRole(RoleName::reviewDecisionRoles())) {
            return false;
        }

        return in_array($document->status, [
            'uploaded',
            'reuploaded',
            'under_review',
        ], true);
    }

    /**
     * Allow review-capable internal users to access the review queue.
     */
    public function viewQueue(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::SuperAdmin,
            RoleName::ComplianceAdmin,
            RoleName::Reviewer,
        ]);
    }

}
