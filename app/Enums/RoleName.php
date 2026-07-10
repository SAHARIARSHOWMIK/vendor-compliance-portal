<?php

namespace App\Enums;

/**
 * The five system roles from the spec, each with a fixed permission set
 * enforced primarily through Policies (app/Policies) and the
 * EnsureUserHasRole middleware, not just route-level checks - so
 * authorization holds even if a route is hit directly or a controller action is invoked directly.
 */
enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case ComplianceAdmin = 'compliance_admin';
    case Reviewer = 'reviewer';
    case VendorUser = 'vendor_user';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ComplianceAdmin => 'Compliance Admin',
            self::Reviewer => 'Reviewer',
            self::VendorUser => 'Vendor User',
            self::Auditor => 'Read-only Auditor',
        };
    }

    /**
     * Roles that operate "internally" (staff side of the portal) as
     * opposed to VendorUser, which is the only external/vendor-facing role.
     */
    public static function internalRoles(): array
    {
        return [self::SuperAdmin, self::ComplianceAdmin, self::Reviewer, self::Auditor];
    }

    /**
     * Roles permitted to make a review decision (approve/reject/
     * correction/escalate) on a document. Auditors are deliberately
     * excluded even though they are "internal" - read-only is read-only.
     */
    public static function reviewDecisionRoles(): array
    {
        return [self::SuperAdmin, self::ComplianceAdmin, self::Reviewer];
    }

    /**
     * Roles permitted to write/mutate vendor or document records at all.
     * Auditor is excluded; VendorUser is included but is further scoped
     * to their own vendor company by EnsureVendorScopedAccess middleware
     * and VendorPolicy/VendorDocumentPolicy.
     */
    public static function writeRoles(): array
    {
        return [self::SuperAdmin, self::ComplianceAdmin, self::Reviewer, self::VendorUser];
    }

    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
