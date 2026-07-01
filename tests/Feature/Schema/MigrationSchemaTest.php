<?php

namespace Tests\Feature\Schema;

use App\Models\DocumentType;
use App\Models\VendorCategoryRequirement;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSchemaTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Table existence
    // -----------------------------------------------------------------

    public function test_all_tables_are_created(): void
    {
        $expected = [
            'users',
            'vendors',
            'vendor_users',
            'document_types',
            'vendor_category_requirements',
            'vendor_documents',
            'document_versions',
            'reviews',
            'compliance_checks',
            'notifications',
            'audit_logs',
        ];

        foreach ($expected as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table '{$table}' was not created by migrations."
            );
        }
    }

    // -----------------------------------------------------------------
    // Critical column presence
    // -----------------------------------------------------------------

    public function test_vendors_table_has_all_spec_columns(): void
    {
        $required = [
            'id', 'name', 'registration_number', 'category', 'risk_level',
            'contact_person', 'email', 'phone', 'address', 'country',
            'status', 'compliance_status', 'compliance_score',
            'assigned_reviewer_id', 'invited_at', 'registered_at',
            'created_at', 'updated_at',
        ];

        foreach ($required as $col) {
            $this->assertTrue(
                Schema::hasColumn('vendors', $col),
                "vendors.{$col} is missing from the schema."
            );
        }
    }

    public function test_vendor_documents_table_has_all_spec_columns(): void
    {
        $required = [
            'id', 'vendor_id', 'document_type_id', 'uploaded_by',
            'file_path', 'original_filename', 'mime_type', 'file_size_kb',
            'version_number', 'status', 'expiry_date', 'uploaded_at',
            'reviewed_at', 'reviewed_by', 'review_comment', 'notes',
        ];

        foreach ($required as $col) {
            $this->assertTrue(
                Schema::hasColumn('vendor_documents', $col),
                "vendor_documents.{$col} is missing from the schema."
            );
        }
    }

    public function test_audit_logs_table_has_immutability_columns(): void
    {
        // old_values / new_values for before-after tracking
        $this->assertTrue(Schema::hasColumn('audit_logs', 'old_values'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'new_values'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'occurred_at'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'actor_name')); // snapshot in case user deleted
        $this->assertTrue(Schema::hasColumn('audit_logs', 'vendor_name')); // snapshot
    }

    public function test_document_versions_table_exists_for_version_history(): void
    {
        $this->assertTrue(Schema::hasTable('document_versions'));
        $this->assertTrue(Schema::hasColumn('document_versions', 'vendor_document_id'));
        $this->assertTrue(Schema::hasColumn('document_versions', 'version_number'));
        $this->assertTrue(Schema::hasColumn('document_versions', 'status_at_snapshot'));
    }

    // -----------------------------------------------------------------
    // DocumentTypeSeeder correctness
    // -----------------------------------------------------------------

    public function test_document_type_seeder_creates_all_9_types(): void
    {
        $this->seed(DocumentTypeSeeder::class);

        $this->assertDatabaseCount('document_types', 9);

        $expectedSlugs = [
            'company_registration', 'tax_certificate', 'bank_verification',
            'business_license', 'insurance_certificate', 'contract', 'nda',
            'safety_certificate', 'cybersecurity_declaration',
        ];

        foreach ($expectedSlugs as $slug) {
            $this->assertDatabaseHas('document_types', ['slug' => $slug]);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(DocumentTypeSeeder::class); // run twice

        $this->assertDatabaseCount('document_types', 9); // still 9, not 18
    }

    public function test_seeder_creates_correct_requirements_for_general_supplier(): void
    {
        $this->seed(DocumentTypeSeeder::class);

        $requiredSlugs = ['company_registration', 'tax_certificate', 'bank_verification', 'contract'];

        foreach ($requiredSlugs as $slug) {
            $docTypeId = DocumentType::where('slug', $slug)->value('id');

            $this->assertDatabaseHas('vendor_category_requirements', [
                'vendor_category'   => 'general_supplier',
                'document_type_id'  => $docTypeId,
                'requirement_level' => 'required',
            ]);
        }
    }

    public function test_seeder_creates_correct_requirements_for_it_vendor(): void
    {
        $this->seed(DocumentTypeSeeder::class);

        $requiredSlugs = [
            'company_registration', 'tax_certificate', 'bank_verification',
            'contract', 'nda', 'cybersecurity_declaration',
        ];

        foreach ($requiredSlugs as $slug) {
            $docTypeId = DocumentType::where('slug', $slug)->value('id');

            $this->assertDatabaseHas('vendor_category_requirements', [
                'vendor_category'   => 'it_vendor',
                'document_type_id'  => $docTypeId,
                'requirement_level' => 'required',
            ]);
        }

        // IT vendor requires exactly 6 documents
        $this->assertSame(
            6,
            VendorCategoryRequirement::where('vendor_category', 'it_vendor')->count()
        );
    }

    public function test_insurance_certificate_has_expiry_required_for_contractor(): void
    {
        $this->seed(DocumentTypeSeeder::class);

        $insuranceId = DocumentType::where('slug', 'insurance_certificate')->value('id');

        $this->assertDatabaseHas('vendor_category_requirements', [
            'vendor_category'  => 'contractor',
            'document_type_id' => $insuranceId,
            'expiry_required'  => true,
        ]);
    }

    public function test_high_risk_vendor_requires_8_documents(): void
    {
        $this->seed(DocumentTypeSeeder::class);

        $count = VendorCategoryRequirement::where('vendor_category', 'high_risk')
            ->where('requirement_level', 'required')
            ->count();

        $this->assertSame(8, $count);
    }

    public function test_expiry_date_required_flag_set_on_correct_document_types(): void
    {
        $this->seed(DocumentTypeSeeder::class);

        $expiryRequired = DocumentType::where('requires_expiry_date', true)->pluck('slug')->sort()->values();
        $expected = collect(['business_license', 'insurance_certificate', 'contract', 'safety_certificate'])->sort()->values();

        $this->assertEquals($expected, $expiryRequired);
    }
}
