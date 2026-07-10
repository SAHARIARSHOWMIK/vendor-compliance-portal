<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\Vendor;

/**
 * Gate::authorize('update', $vendor) and @can('update', $vendor) in
 * Blade templates and controllers both delegate here. This is the second authorization
 * layer (middleware is the first) that enforces finer distinctions than
 * "which role can see this route" - e.g. a Reviewer can *view* any
 * vendor but can only *make review decisions* on assigned ones.
 */
class VendorPolicy
{
    /** Everyone authenticated can view vendor records (Auditor: read-only). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->isVendorUser()) {
            return $user->vendor()?->id === $vendor->id;
        }
        return true;
    }

    /** Only admin roles can create vendors (not reviewers, not vendors). */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin]);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin]);
    }

    /** Suspend requires ComplianceAdmin or SuperAdmin. */
    public function suspend(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin])
            && $vendor->status !== 'suspended';
    }

    public function reinstate(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin])
            && $vendor->status === 'suspended';
    }

    public function archive(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin])
            && $vendor->status !== 'archived';
    }

    /** Sending invitations is an admin action. */
    public function invite(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin])
            && ! $vendor->isArchived()
            && ! $vendor->isSuspended();
    }

    /** Assign reviewer: only admin roles. */
    public function assignReviewer(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin, RoleName::ComplianceAdmin]);
    }

    /** Auditors can export but cannot edit. */
    public function export(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::SuperAdmin,
            RoleName::ComplianceAdmin,
            RoleName::Auditor,
        ]);
    }

    /**
     * Delete is intentionally not implemented - vendors should be archived,
     * not deleted, to preserve the audit trail. Hardcode to false.
     */
    public function delete(User $user, Vendor $vendor): bool
    {
        return false;
    }
}
