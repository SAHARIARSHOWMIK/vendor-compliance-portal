<?php

namespace Tests\Feature\Compliance;

use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorUser;
use App\Services\ComplianceService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplianceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTypeSeeder::class);
        Storage::fake('vendor_documents');
        Mail::fake();
    }

    private function makeVendorWithUser(string $category = 'general_supplier'): array
    {
        $vendor   = Vendor::factory()->create(['category' => $category, 'status' => 'under_review', 'risk_level' => 'low']);
        $user     = User::factory()->create(['role' => RoleName::VendorUser]);
        VendorUser::factory()->create(['user_id' => $user->id, 'vendor_id' => $vendor->id]);
        return [$vendor, $user];
    }

    private function makeDocument(Vendor $vendor, string $slug, string $status, ?string $expiryDate = null): VendorDocument
    {
        $docType  = DocumentType::where('slug', $slug)->first();
        $uploader = User::factory()->create(['role' => RoleName::VendorUser]);
        return VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'uploaded_by'      => $uploader->id,
            'status'           => $status,
            'expiry_date'      => $expiryDate,
        ]);
    }

    // -----------------------------------------------------------------
    // Core compliance calculation
    // -----------------------------------------------------------------

    public function test_vendor_with_all_required_docs_approved_is_fully_compliant(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        // general_supplier requires: company_registration, tax_certificate, bank_verification, contract
        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }

        $service = app(ComplianceService::class);
        $check   = $service->recalculate($vendor);

        $this->assertSame('fully_compliant', $check->overall_status);
        $this->assertSame(100, $check->compliance_score);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'compliance_status' => 'fully_compliant', 'compliance_score' => 100]);
    }

    public function test_vendor_with_missing_required_doc_is_documents_missing(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        // Only 3 of 4 required docs uploaded
        foreach (['company_registration', 'tax_certificate', 'bank_verification'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }

        $check = app(ComplianceService::class)->recalculate($vendor);

        $this->assertSame('documents_missing', $check->overall_status);
        $this->assertSame(1, $check->total_missing);
        $this->assertLessThan(100, $check->compliance_score);
    }

    public function test_vendor_with_rejected_doc_is_correction_required(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        $this->makeDocument($vendor, 'company_registration', 'approved');
        $this->makeDocument($vendor, 'tax_certificate', 'approved');
        $this->makeDocument($vendor, 'bank_verification', 'rejected');
        $this->makeDocument($vendor, 'contract', 'approved');

        $check = app(ComplianceService::class)->recalculate($vendor);

        $this->assertSame('correction_required', $check->overall_status);
        $this->assertSame(1, $check->total_rejected);
    }

    public function test_vendor_with_expired_doc_is_non_compliant(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        foreach (['company_registration', 'tax_certificate', 'bank_verification'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }
        $this->makeDocument($vendor, 'contract', 'expired', now()->subDay()->toDateString());

        $check = app(ComplianceService::class)->recalculate($vendor);

        $this->assertSame('non_compliant', $check->overall_status);
        $this->assertSame(1, $check->total_expired);
        $this->assertLessThan(100, $check->compliance_score);
    }

    public function test_vendor_with_expiring_soon_doc_gets_expiring_soon_status(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        foreach (['company_registration', 'tax_certificate', 'bank_verification'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }
        $this->makeDocument($vendor, 'contract', 'expiring_soon', now()->addDays(5)->toDateString());

        $check = app(ComplianceService::class)->recalculate($vendor);

        $this->assertSame('expiring_soon', $check->overall_status);
        $this->assertSame(1, $check->total_expiring_soon);
    }

    public function test_vendor_with_docs_under_review_is_under_review(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $this->makeDocument($vendor, $slug, 'under_review');
        }

        $check = app(ComplianceService::class)->recalculate($vendor);

        $this->assertSame('under_review', $check->overall_status);
        $this->assertSame(4, $check->total_pending_review);
    }

    public function test_suspended_vendor_always_stays_suspended(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        $vendor->update(['status' => 'suspended', 'compliance_status' => 'suspended']);
        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }

        $check = app(ComplianceService::class)->recalculate($vendor);

        // Even with all docs approved, suspended vendor stays suspended
        $this->assertSame('suspended', $check->overall_status);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'suspended']);
    }

    // -----------------------------------------------------------------
    // Compliance score
    // -----------------------------------------------------------------

    public function test_compliance_score_is_100_when_fully_compliant(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }

        $check = app(ComplianceService::class)->recalculate($vendor);
        $this->assertSame(100, $check->compliance_score);
    }

    public function test_compliance_score_is_0_when_all_docs_missing(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        // No documents uploaded at all

        $check = app(ComplianceService::class)->recalculate($vendor);
        $this->assertSame(0, $check->compliance_score);
    }

    public function test_compliance_score_reduces_for_expired_documents(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');
        foreach (['company_registration', 'tax_certificate', 'bank_verification'] as $slug) {
            $this->makeDocument($vendor, $slug, 'approved');
        }
        $this->makeDocument($vendor, 'contract', 'expired', now()->subDay()->toDateString());

        $check = app(ComplianceService::class)->recalculate($vendor);
        // 3/4 approved * 100 = 75, minus 1/4 expired * 40 = 10 -> 65
        $this->assertSame(65, $check->compliance_score);
    }

    // -----------------------------------------------------------------
    // Compliance check is persisted
    // -----------------------------------------------------------------

    public function test_recalculate_appends_compliance_check_row(): void
    {
        [$vendor] = $this->makeVendorWithUser('general_supplier');

        app(ComplianceService::class)->recalculate($vendor);
        app(ComplianceService::class)->recalculate($vendor); // twice

        $this->assertSame(2, \App\Models\ComplianceCheck::where('vendor_id', $vendor->id)->count());
    }

    // -----------------------------------------------------------------
    // Expiry monitoring command
    // -----------------------------------------------------------------

    public function test_expiry_command_marks_expired_documents(): void
    {
        [$vendor] = $this->makeVendorWithUser('contractor');
        $this->makeDocument($vendor, 'insurance_certificate', 'approved', now()->subDay()->toDateString());

        $this->artisan('compliance:check-expiry')->assertSuccessful();

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id' => $vendor->id,
            'status'    => 'expired',
        ]);
    }

    public function test_expiry_command_marks_expiring_soon_documents(): void
    {
        [$vendor] = $this->makeVendorWithUser('contractor');
        $this->makeDocument($vendor, 'insurance_certificate', 'approved', now()->addDays(5)->toDateString());

        $this->artisan('compliance:check-expiry')->assertSuccessful();

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id' => $vendor->id,
            'status'    => 'expiring_soon',
        ]);
    }

    public function test_expiry_command_dry_run_makes_no_changes(): void
    {
        [$vendor] = $this->makeVendorWithUser('contractor');
        $this->makeDocument($vendor, 'insurance_certificate', 'approved', now()->subDay()->toDateString());

        $this->artisan('compliance:check-expiry --dry-run')->assertSuccessful();

        // Status must NOT have changed in dry-run mode
        $this->assertDatabaseMissing('vendor_documents', [
            'vendor_id' => $vendor->id,
            'status'    => 'expired',
        ]);
    }

    public function test_expiry_command_logs_expired_document_to_audit_trail(): void
    {
        [$vendor] = $this->makeVendorWithUser('contractor');
        $this->makeDocument($vendor, 'insurance_certificate', 'approved', now()->subDay()->toDateString());

        $this->artisan('compliance:check-expiry')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'document_expired',
            'vendor_id'  => $vendor->id,
        ]);
    }

    public function test_expiry_command_triggers_compliance_recalculation(): void
    {
        [$vendor] = $this->makeVendorWithUser('contractor');
        $this->makeDocument($vendor, 'insurance_certificate', 'approved', now()->subDay()->toDateString());

        $this->artisan('compliance:check-expiry')->assertSuccessful();

        $this->assertDatabaseHas('compliance_checks', ['vendor_id' => $vendor->id]);
    }

    // -----------------------------------------------------------------
    // Notifications
    // -----------------------------------------------------------------

    public function test_expiry_command_creates_in_app_notification(): void
    {
        [$vendor, $vendorUser] = $this->makeVendorWithUser('contractor');
        $this->makeDocument($vendor, 'insurance_certificate', 'approved', now()->subDay()->toDateString());

        $this->artisan('compliance:check-expiry')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id'   => $vendorUser->id,
            'type'      => 'urgent',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_review_approval_sends_email_to_vendor_user(): void
    {
        [$vendor, $vendorUser] = $this->makeVendorWithUser('general_supplier');
        $docType  = DocumentType::where('slug', 'company_registration')->first();
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'uploaded_by'      => $vendorUser->id,
            'status'           => 'uploaded',
        ]);

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), [
                'decision' => 'approved',
                'comment'  => '',
            ]);

        Mail::assertSent(\App\Mail\DocumentReviewedMail::class, fn ($mail) =>
            $mail->hasTo($vendorUser->email)
        );
    }

    // -----------------------------------------------------------------
    // Audit log
    // -----------------------------------------------------------------

    public function test_audit_log_captures_actor_snapshot(): void
    {
        [$vendor, $vendorUser] = $this->makeVendorWithUser('general_supplier');
        $docType  = DocumentType::where('slug', 'company_registration')->first();
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer, 'name' => 'Jane Reviewer']);
        $document = VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'uploaded_by'      => $vendorUser->id,
            'status'           => 'uploaded',
        ]);

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), [
                'decision' => 'approved',
                'comment'  => '',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'document_approved',
            'actor_name' => 'Jane Reviewer',
            'actor_role' => 'reviewer',
        ]);
    }

    public function test_audit_log_captures_old_and_new_values(): void
    {
        [$vendor, $vendorUser] = $this->makeVendorWithUser('general_supplier');
        $docType  = DocumentType::where('slug', 'company_registration')->first();
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'uploaded_by'      => $vendorUser->id,
            'status'           => 'uploaded',
        ]);

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), [
                'decision' => 'rejected',
                'comment'  => 'Wrong document.',
            ]);

        $log = \App\Models\AuditLog::where('event_type', 'document_rejected')->first();
        $this->assertNotNull($log);
        $this->assertSame('uploaded', $log->old_values['status']);
        $this->assertSame('rejected', $log->new_values['status']);
    }
}
