<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\VendorDocument;

class ReviewPolicy
{
    /**
     * Can this user make a review decision on this document?
     *
     * - SuperAdmin and ComplianceAdmin: always yes (if status allows)
     * - Reviewer: yes
     * - VendorUser / Auditor: never
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

    public function viewQueue(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::SuperAdmin,
            RoleName::ComplianceAdmin,
            RoleName::Reviewer,
        ]);
    }
}
