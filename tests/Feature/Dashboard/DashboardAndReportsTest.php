<?php

namespace Tests\Feature\Dashboard;

use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorUser;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTypeSeeder::class);
        Storage::fake('vendor_documents');
        Mail::fake();
    }

    // -----------------------------------------------------------------
    // Admin dashboard
    // -----------------------------------------------------------------

    public function test_admin_dashboard_shows_correct_vendor_counts(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        Vendor::factory()->create(['compliance_status' => 'fully_compliant']);
        Vendor::factory()->create(['compliance_status' => 'fully_compliant']);
        Vendor::factory()->create(['compliance_status' => 'non_compliant']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('fullyCompliant', 2);
        $response->assertViewHas('nonCompliant', 1);
    }

    public function test_admin_dashboard_excludes_archived_vendors_from_total(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        Vendor::factory()->create(['status' => 'registered']);
        Vendor::factory()->create(['status' => 'archived']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('totalVendors', 1);
    }

    public function test_reviewer_cannot_access_admin_dashboard(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $this->actingAs($reviewer)->get(route('admin.dashboard'))->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Reports — access control
    // -----------------------------------------------------------------

    public function test_compliance_admin_can_access_reports_index(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertStatus(200);
    }

    public function test_auditor_can_access_reports(): void
    {
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);
        $this->actingAs($auditor)->get(route('admin.reports.compliance-summary'))->assertStatus(200);
    }

    public function test_reviewer_cannot_access_reports(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $this->actingAs($reviewer)->get(route('admin.reports.index'))->assertStatus(403);
    }

    public function test_vendor_user_cannot_access_reports(): void
    {
        $vendorUser = User::factory()->create(['role' => RoleName::VendorUser]);
        $this->actingAs($vendorUser)->get(route('admin.reports.compliance-summary'))->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Compliance summary report
    // -----------------------------------------------------------------

    public function test_compliance_summary_report_shows_vendors(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        Vendor::factory()->create(['name' => 'Test Vendor Alpha']);

        $response = $this->actingAs($admin)->get(route('admin.reports.compliance-summary'));

        $response->assertStatus(200)->assertSee('Test Vendor Alpha');
    }

    public function test_compliance_summary_csv_export_has_correct_headers(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        Vendor::factory()->create(['name' => 'CSV Test Vendor']);

        $response = $this->actingAs($admin)->get(route('admin.reports.compliance-summary.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Vendor Name', $content);
        $this->assertStringContainsString('CSV Test Vendor', $content);
    }

    public function test_csv_export_creates_audit_log_entry(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        Vendor::factory()->create();

        // logExport() runs in the controller method body before the
        // StreamedResponse closure is even constructed, so it fires on
        // every request to this route regardless of whether the test
        // consumes the streamed CSV body.
        $this->actingAs($admin)->get(route('admin.reports.compliance-summary.export'));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'report_exported',
            'actor_id'   => $admin->id,
        ]);
    }

    // -----------------------------------------------------------------
    // Missing documents report
    // -----------------------------------------------------------------

    public function test_missing_documents_report_finds_incomplete_vendors(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['category' => 'general_supplier', 'status' => 'documents_pending']);
        // No documents uploaded at all — all 4 required docs missing

        $response = $this->actingAs($admin)->get(route('admin.reports.missing-documents'));

        $response->assertStatus(200);
        $response->assertSee($vendor->name);
    }

    public function test_missing_documents_report_excludes_fully_compliant_vendors(): void
    {
        $admin  = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor = Vendor::factory()->create(['category' => 'general_supplier', 'status' => 'fully_compliant']);

        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $docType = DocumentType::where('slug', $slug)->first();
            VendorDocument::factory()->create([
                'vendor_id'        => $vendor->id,
                'document_type_id' => $docType->id,
                'status'           => 'approved',
            ]);
        }

        $rows = app(\App\Services\ReportService::class)->missingDocumentsData();
        $vendorRows = array_filter($rows, fn ($r) => $r['vendor']->id === $vendor->id);

        $this->assertEmpty($vendorRows);
    }

    // -----------------------------------------------------------------
    // Expiring documents report
    // -----------------------------------------------------------------

    public function test_expiring_documents_report_filters_by_window(): void
    {
        $admin   = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor  = Vendor::factory()->create();
        $docType = DocumentType::where('slug', 'insurance_certificate')->first();

        VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'status'           => 'approved',
            'expiry_date'      => now()->addDays(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.expiring-documents', ['within_days' => 7]));

        $response->assertStatus(200)->assertSee($vendor->name);
    }

    // -----------------------------------------------------------------
    // Rejected documents report
    // -----------------------------------------------------------------

    public function test_rejected_documents_report_shows_correction_requested_too(): void
    {
        $admin   = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendor  = Vendor::factory()->create();
        $docType = DocumentType::where('slug', 'bank_verification')->first();

        VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'status'           => 'correction_requested',
            'review_comment'   => 'Please clarify.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.rejected-documents'));

        $response->assertStatus(200)->assertSee('Please clarify.');
    }

    // -----------------------------------------------------------------
    // Audit log report
    // -----------------------------------------------------------------

    public function test_audit_log_report_can_filter_by_vendor(): void
    {
        $admin   = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $vendorA = Vendor::factory()->create(['name' => 'Vendor A']);
        $vendorB = Vendor::factory()->create(['name' => 'Vendor B']);

        app(\App\Services\AuditService::class)->log('vendor_created', 'Vendor A created.', $admin, $vendorA);
        app(\App\Services\AuditService::class)->log('vendor_created', 'Vendor B created.', $admin, $vendorB);

        $response = $this->actingAs($admin)->get(route('admin.reports.audit-log', ['vendor_id' => $vendorA->id]));

        $response->assertStatus(200);
        $response->assertSee('Vendor A created.');
        $response->assertDontSee('Vendor B created.');
    }

    public function test_auditor_can_view_audit_log_but_not_edit_anything(): void
    {
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);
        $vendor  = Vendor::factory()->create();

        $this->actingAs($auditor)->get(route('admin.reports.audit-log'))->assertStatus(200);

        // Auditor cannot suspend a vendor
        $this->actingAs($auditor)
            ->post(route('admin.vendors.suspend', $vendor), ['reason' => 'test'])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Auditor read-only views
    // -----------------------------------------------------------------

    public function test_auditor_can_view_vendor_detail_with_full_history(): void
    {
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);
        $vendor  = Vendor::factory()->create();

        $this->actingAs($auditor)
            ->get(route('auditor.vendors.show', $vendor))
            ->assertStatus(200)
            ->assertSee($vendor->name);
    }

    public function test_vendor_user_cannot_access_auditor_vendor_list(): void
    {
        $vendorUser = User::factory()->create(['role' => RoleName::VendorUser]);
        $this->actingAs($vendorUser)->get(route('auditor.vendors'))->assertStatus(403);
    }
}
