<?php

namespace Tests\Feature\Vendor;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Vendor creation (admin)
    // -----------------------------------------------------------------

    public function test_compliance_admin_can_create_vendor(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $response = $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'name'       => 'Test Vendor Sdn Bhd',
            'category'   => 'general_supplier',
            'risk_level' => 'low',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'name'     => 'Test Vendor Sdn Bhd',
            'category' => 'general_supplier',
            'status'   => 'draft',
        ]);
    }

    public function test_new_vendor_starts_in_draft_status(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'name'       => 'Draft Vendor',
            'category'   => 'contractor',
            'risk_level' => 'medium',
        ]);

        $this->assertDatabaseHas('vendors', ['name' => 'Draft Vendor', 'status' => 'draft']);
    }

    public function test_vendor_creation_is_logged_in_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'name'       => 'Audited Vendor',
            'category'   => 'it_vendor',
            'risk_level' => 'high',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'vendor_created',
            'actor_id'   => $admin->id,
        ]);
    }

    public function test_reviewer_cannot_create_vendor(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);

        $response = $this->actingAs($reviewer)->post(route('admin.vendors.store'), [
            'name'       => 'Unauthorized Vendor',
            'category'   => 'general_supplier',
            'risk_level' => 'low',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('vendors', ['name' => 'Unauthorized Vendor']);
    }

    public function test_vendor_user_cannot_create_vendor(): void
    {
        $vendorUser = User::factory()->create(['role' => RoleName::VendorUser]);

        $this->actingAs($vendorUser)
            ->post(route('admin.vendors.store'), ['name' => 'Bad Vendor', 'category' => 'general_supplier', 'risk_level' => 'low'])
            ->assertStatus(403);
    }

    public function test_auditor_cannot_create_vendor(): void
    {
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);

        $this->actingAs($auditor)
            ->post(route('admin.vendors.store'), ['name' => 'Bad Vendor', 'category' => 'general_supplier', 'risk_level' => 'low'])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    public function test_vendor_name_is_required(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $response = $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'category'   => 'general_supplier',
            'risk_level' => 'low',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_invalid_category_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $response = $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'name'       => 'Test Vendor',
            'category'   => 'made_up_category',
            'risk_level' => 'low',
        ]);

        $response->assertSessionHasErrors('category');
    }

    // -----------------------------------------------------------------
    // Read
    // -----------------------------------------------------------------

    public function test_compliance_admin_can_view_vendor_list(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        Vendor::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('admin.vendors.index'))->assertStatus(200);
    }

    public function test_auditor_can_view_vendor_list(): void
    {
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);
        $this->actingAs($auditor)->get(route('admin.vendors.index'))->assertStatus(200);
    }

    public function test_vendor_user_cannot_see_other_vendors(): void
    {
        $vendorUser = User::factory()->create(['role' => RoleName::VendorUser]);
        $otherVendor = Vendor::factory()->create();

        $this->actingAs($vendorUser)
            ->get(route('admin.vendors.show', $otherVendor))
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Invitation flow
    // -----------------------------------------------------------------

    public function test_admin_can_invite_vendor_user(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($admin)->post(route('admin.vendors.invite', $vendor), [
            'email'        => 'contact@newvendor.test',
            'contact_name' => 'Test Contact',
        ]);

        $response->assertRedirect(route('admin.vendors.show', $vendor));

        // User account created
        $this->assertDatabaseHas('users', ['email' => 'contact@newvendor.test', 'role' => 'vendor_user']);

        // VendorUser link created with pending invitation
        $user = User::where('email', 'contact@newvendor.test')->first();
        $this->assertDatabaseHas('vendor_users', [
            'user_id'          => $user->id,
            'vendor_id'        => $vendor->id,
            'invitation_status' => 'pending',
        ]);

        // Vendor status advanced to 'invited'
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'invited']);
    }

    public function test_invitation_sends_email_notification(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create();

        $this->actingAs($admin)->post(route('admin.vendors.invite', $vendor), [
            'email'        => 'invite@test.com',
            'contact_name' => 'Test',
        ]);

        Notification::assertSentTo(
            User::where('email', 'invite@test.com')->first(),
            \App\Notifications\VendorInvitationNotification::class,
        );
    }

    public function test_invitation_is_logged_in_audit_trail(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create();

        $this->actingAs($admin)->post(route('admin.vendors.invite', $vendor), [
            'email'        => 'audit@test.com',
            'contact_name' => 'Audit Test',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'vendor_invited',
            'vendor_id'  => $vendor->id,
        ]);
    }

    public function test_reviewer_cannot_invite_vendor_user(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $vendor   = Vendor::factory()->create();

        $this->actingAs($reviewer)
            ->post(route('admin.vendors.invite', $vendor), ['email' => 'x@y.com', 'contact_name' => 'X'])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Lifecycle transitions
    // -----------------------------------------------------------------

    public function test_admin_can_suspend_vendor(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['status' => 'fully_compliant']);

        $this->actingAs($admin)->post(route('admin.vendors.suspend', $vendor), [
            'reason' => 'Compliance failure detected.',
        ]);

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('audit_logs', ['event_type' => 'vendor_suspended', 'vendor_id' => $vendor->id]);
    }

    public function test_admin_can_reinstate_suspended_vendor(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['status' => 'suspended']);

        $this->actingAs($admin)->post(route('admin.vendors.reinstate', $vendor));

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'registered']);
    }

    public function test_cannot_suspend_already_suspended_vendor(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['status' => 'suspended']);

        $response = $this->actingAs($admin)->post(route('admin.vendors.suspend', $vendor), [
            'reason' => 'Already suspended.',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_archive_vendor(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['status' => 'registered']);

        $this->actingAs($admin)->post(route('admin.vendors.archive', $vendor));

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'archived']);
    }

    public function test_vendor_cannot_be_deleted_only_archived(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::SuperAdmin]);
        $vendor = Vendor::factory()->create();

        // destroy route is explicitly excluded via ->except(['destroy']),
        // The URI exists for other methods, so DELETE returns 405 Method Not Allowed.
        $response = $this->actingAs($admin)->delete(route('admin.vendors.index') . '/' . $vendor->id);
        $response->assertStatus(405);
    }

    // -----------------------------------------------------------------
    // Demo seeder
    // -----------------------------------------------------------------

    public function test_demo_seeder_creates_all_5_vendors(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->assertDatabaseCount('vendors', 5);
        $this->assertDatabaseHas('vendors', ['name' => 'Alpha Office Supplies', 'status' => 'fully_compliant']);
        $this->assertDatabaseHas('vendors', ['name' => 'BuildPro Contractors Sdn Bhd', 'status' => 'expiring_soon']);
        $this->assertDatabaseHas('vendors', ['name' => 'Noor Consulting', 'compliance_status' => 'correction_required']);
    }

    public function test_demo_seeder_creates_vendor_user_accounts_for_all_roles(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        foreach (['super_admin', 'compliance_admin', 'reviewer', 'auditor'] as $role) {
            $this->assertDatabaseHas('users', ['role' => $role]);
        }
        // 4 internal staff + 5 vendor users = 9 total minimum
        $this->assertGreaterThanOrEqual(9, \App\Models\User::count());
    }

    public function test_demo_seeder_creates_rejected_bank_doc_for_noor_consulting(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $noor = Vendor::where('name', 'Noor Consulting')->first();
        $this->assertNotNull($noor);

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id' => $noor->id,
            'status'    => 'rejected',
        ]);
    }
}
