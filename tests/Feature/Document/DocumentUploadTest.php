<?php

namespace Tests\Feature\Document;

use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorUser;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTypeSeeder::class);
        // Use a fake disk so tests never touch the real filesystem
        Storage::fake('vendor_documents');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeVendorUser(Vendor $vendor): User
    {
        $user = User::factory()->create(['role' => RoleName::VendorUser]);
        VendorUser::factory()->create([
            'user_id'          => $user->id,
            'vendor_id'        => $vendor->id,
            'invitation_status' => 'accepted',
        ]);
        return $user;
    }

    private function docType(string $slug): DocumentType
    {
        return DocumentType::where('slug', $slug)->firstOrFail();
    }

    private function fakePdf(string $name = 'document.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 500, 'application/pdf');
    }

    // -----------------------------------------------------------------
    // Basic upload
    // -----------------------------------------------------------------

    public function test_vendor_user_can_upload_document_for_their_vendor(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('company_registration');

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf('ssm_cert.pdf'),
            ]);

        $response->assertRedirect(route('vendor-portal.checklist'));

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'status'           => 'uploaded',
            'version_number'   => 1,
        ]);
    }

    public function test_file_is_stored_on_private_disk_not_public(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('company_registration');

        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf('private_doc.pdf'),
            ]);

        $doc = VendorDocument::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($doc);

        // File must be on the vendor_documents disk, NOT the public disk
        Storage::disk('vendor_documents')->assertExists($doc->file_path);
        Storage::disk('public')->assertMissing($doc->file_path);
    }

    public function test_upload_creates_audit_log_entry(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('tax_certificate');

        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'document_uploaded',
            'vendor_id'  => $vendor->id,
            'actor_id'   => $vendorUser->id,
        ]);
    }

    // -----------------------------------------------------------------
    // Version history
    // -----------------------------------------------------------------

    public function test_reuploading_creates_version_history_snapshot(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'correction_required']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('bank_verification');

        // Initial upload
        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf('bank_v1.pdf'),
            ]);

        $doc = VendorDocument::where('vendor_id', $vendor->id)
            ->where('document_type_id', $docType->id)
            ->first();

        // Force status to rejected so reupload is allowed
        $doc->update(['status' => 'rejected']);

        // Reupload
        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf('bank_v2.pdf'),
            ]);

        // vendor_documents row is updated to v2
        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'version_number'   => 2,
            'status'           => 'reuploaded',
        ]);

        // v1 is preserved in document_versions
        $this->assertDatabaseHas('document_versions', [
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'version_number'   => 1,
        ]);
    }

    public function test_old_file_path_is_preserved_in_version_history(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('contract');

        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf('contract_v1.pdf'),
                'expiry_date'      => now()->addYear()->format('Y-m-d'),
            ]);

        $doc = VendorDocument::where('vendor_id', $vendor->id)->first();
        $originalPath = $doc->file_path;

        $doc->update(['status' => 'correction_requested']);

        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf('contract_v2.pdf'),
                'expiry_date'      => now()->addYear()->format('Y-m-d'),
            ]);

        // The old path must exist in document_versions
        $this->assertDatabaseHas('document_versions', [
            'vendor_document_id' => $doc->id,
            'file_path'          => $originalPath,
        ]);
    }

    // -----------------------------------------------------------------
    // Expiry date validation
    // -----------------------------------------------------------------

    public function test_expiry_date_required_for_insurance_certificate(): void
    {
        $vendor     = Vendor::factory()->create(['category' => 'contractor', 'status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('insurance_certificate');

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
                // expiry_date intentionally omitted
            ]);

        $response->assertSessionHasErrors('expiry_date');
        $this->assertDatabaseMissing('vendor_documents', [
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
        ]);
    }

    public function test_expiry_date_must_be_in_the_future(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('business_license');

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
                'expiry_date'      => now()->subDay()->format('Y-m-d'), // yesterday
            ]);

        $response->assertSessionHasErrors('expiry_date');
    }

    public function test_expiry_date_optional_for_company_registration(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('company_registration');

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
                // no expiry_date — should be fine for this doc type
            ]);

        $response->assertSessionMissingErrors('expiry_date');
        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
        ]);
    }

    // -----------------------------------------------------------------
    // File validation
    // -----------------------------------------------------------------

    public function test_invalid_file_type_is_rejected(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('company_registration');

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('vendor_documents', ['vendor_id' => $vendor->id]);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('company_registration');

        // Create a file larger than the 10MB limit
        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf'), // 11MB
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_document_type_id_is_required(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'file' => $this->fakePdf(),
            ]);

        $response->assertSessionHasErrors('document_type_id');
    }

    // -----------------------------------------------------------------
    // Permission gates
    // -----------------------------------------------------------------

    public function test_vendor_user_cannot_upload_for_another_vendor(): void
    {
        $ownVendor   = Vendor::factory()->create(['status' => 'documents_pending']);
        $otherVendor = Vendor::factory()->create(['status' => 'documents_pending']);
        $vendorUser  = $this->makeVendorUser($ownVendor);
        $docType     = $this->docType('company_registration');

        $response = $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $otherVendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('vendor_documents', ['vendor_id' => $otherVendor->id]);
    }

    public function test_auditor_cannot_upload_documents(): void
    {
        $vendor  = Vendor::factory()->create(['status' => 'documents_pending']);
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);
        $docType = $this->docType('company_registration');

        // Test via the admin route (auditors have internal roles, so role middleware
        // would let them through — but VendorDocumentPolicy::upload() blocks auditors)
        $this->actingAs($auditor)
            ->post(route('admin.vendors.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
            ])
            ->assertStatus(403);
    }

    public function test_compliance_admin_can_upload_on_behalf_of_vendor(): void
    {
        $vendor  = Vendor::factory()->create(['status' => 'documents_pending']);
        $admin   = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $docType = $this->docType('tax_certificate');

        // Admins upload via the ADMIN route group, not the vendor-portal route
        // (vendor-portal is role:vendor_user only)
        $this->actingAs($admin)
            ->post(route('admin.vendors.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
            ])
            ->assertRedirect(route('admin.vendors.show', $vendor));

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'uploaded_by'      => $admin->id,
        ]);
    }

    public function test_suspended_vendor_cannot_upload_documents(): void
    {
        $vendor     = Vendor::factory()->create(['status' => 'suspended']);
        $vendorUser = $this->makeVendorUser($vendor);
        $docType    = $this->docType('company_registration');

        $this->actingAs($vendorUser)
            ->post(route('vendor-portal.documents.store', $vendor), [
                'document_type_id' => $docType->id,
                'file'             => $this->fakePdf(),
            ])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Document checklist
    // -----------------------------------------------------------------

    public function test_checklist_shows_missing_documents_for_vendor_category(): void
    {
        $vendor     = Vendor::factory()->create(['category' => 'it_vendor', 'status' => 'documents_pending']);
        $vendorUser = $this->makeVendorUser($vendor);

        $response = $this->actingAs($vendorUser)->get(route('vendor-portal.checklist'));

        $response->assertStatus(200);
        // IT vendor requires 6 documents — all should show as missing initially
        $response->assertSee('Company Registration');
        $response->assertSee('NDA / Confidentiality Agreement');
        $response->assertSee('Cybersecurity Declaration');
    }

    public function test_download_requires_authentication(): void
    {
        $vendor  = Vendor::factory()->create();
        $docType = $this->docType('company_registration');
        $doc     = VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
        ]);

        $this->get(route('vendor-portal.documents.download', $doc))
            ->assertRedirect(route('login'));
    }

    public function test_vendor_user_cannot_download_other_vendors_documents(): void
    {
        $vendor1     = Vendor::factory()->create();
        $vendor2     = Vendor::factory()->create();
        $vendorUser1 = $this->makeVendorUser($vendor1);
        $docType     = $this->docType('company_registration');

        $doc = VendorDocument::factory()->create([
            'vendor_id'        => $vendor2->id,
            'document_type_id' => $docType->id,
            'file_path'        => 'demo/2/company_registration_v1.pdf',
        ]);

        $this->actingAs($vendorUser1)
            ->get(route('vendor-portal.documents.download', $doc))
            ->assertStatus(403);
    }
}
