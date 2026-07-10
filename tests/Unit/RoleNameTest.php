<?php

namespace Tests\Unit;

use App\Enums\RoleName;
use PHPUnit\Framework\TestCase;

class RoleNameTest extends TestCase
{
    public function test_all_expected_role_values_are_available(): void
    {
        $this->assertSame([
            'super_admin',
            'compliance_admin',
            'reviewer',
            'vendor_user',
            'auditor',
        ], RoleName::values());
    }

    public function test_auditor_is_internal_but_read_only(): void
    {
        $this->assertContains(RoleName::Auditor, RoleName::internalRoles());
        $this->assertNotContains(RoleName::Auditor, RoleName::reviewDecisionRoles());
        $this->assertNotContains(RoleName::Auditor, RoleName::writeRoles());
    }

    public function test_reviewer_can_make_review_decisions(): void
    {
        $this->assertContains(RoleName::Reviewer, RoleName::reviewDecisionRoles());
        $this->assertContains(RoleName::Reviewer, RoleName::writeRoles());
    }

    public function test_role_labels_are_human_readable(): void
    {
        $superAdmin = RoleName::SuperAdmin;
        $auditor = RoleName::Auditor;

        $this->assertSame('Super Admin', $superAdmin->label());
        $this->assertSame('Read-only Auditor', $auditor->label());
    }
}
