<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds demo accounts (one per role) and 5 demo vendors from spec
 * section 18, each with pre-built document situations so the dashboard
 * and report pages show meaningful data immediately.
 *
 * Demo credentials (all passwords: password):
 *   super.admin@demo.test       - Super Admin
 *   compliance.admin@demo.test  - Compliance Admin
 *   reviewer@demo.test          - Reviewer
 *   auditor@demo.test           - Auditor
 *   vendor.alpha@demo.test      - Vendor User (Alpha Office Supplies)
 *   vendor.cybernet@demo.test   - Vendor User (CyberNet Solutions)
 *   vendor.buildpro@demo.test   - Vendor User (BuildPro Contractors)
 *   vendor.noor@demo.test       - Vendor User (Noor Consulting)
 *   vendor.securegate@demo.test - Vendor User (SecureGate Systems)
 */
class DemoSeeder extends Seeder
{
    private array $docTypeIds = [];

    public function run(): void
    {
        // Must run DocumentTypeSeeder first to have document types available
        $this->call(DocumentTypeSeeder::class);

        // Cache doc type IDs by slug for use below
        DocumentType::all()->each(function ($dt) {
            $this->docTypeIds[$dt->slug] = $dt->id;
        });

        $reviewer = $this->createInternalUsers();
        $this->createDemoVendors($reviewer);
    }

    // -----------------------------------------------------------------
    // Internal user accounts (one per staff role)
    // -----------------------------------------------------------------

    private function createInternalUsers(): User
    {
        $users = [
            ['name' => 'Super Admin',      'email' => 'super.admin@demo.test',      'role' => 'super_admin'],
            ['name' => 'Compliance Admin', 'email' => 'compliance.admin@demo.test', 'role' => 'compliance_admin'],
            ['name' => 'Demo Reviewer',    'email' => 'reviewer@demo.test',         'role' => 'reviewer'],
            ['name' => 'Audit Officer',    'email' => 'auditor@demo.test',          'role' => 'auditor'],
        ];

        $reviewer = null;
        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'role'     => $data['role'],
                    'status'   => 'active',
                ],
            );
            if ($data['role'] === 'reviewer') {
                $reviewer = $user;
            }
        }

        return $reviewer;
    }

    // -----------------------------------------------------------------
    // 5 demo vendors from spec section 18
    // -----------------------------------------------------------------

    private function createDemoVendors(User $reviewer): void
    {
        // 1. Alpha Office Supplies — General Supplier, Low risk, Fully compliant
        $alpha = $this->createVendor([
            'name'                 => 'Alpha Office Supplies',
            'registration_number'  => 'SSM-001-ALPHA',
            'category'             => 'general_supplier',
            'risk_level'           => 'low',
            'contact_person'       => 'Ahmad Razif',
            'email'                => 'ahmad@alphaoffice.test',
            'country'              => 'MY',
            'status'               => 'fully_compliant',
            'compliance_status'    => 'fully_compliant',
            'compliance_score'     => 100,
            'assigned_reviewer_id' => $reviewer->id,
        ]);
        $this->createVendorUserAccount('vendor.alpha@demo.test', 'Alpha Contact', $alpha, 'accepted');
        // All 4 required docs approved
        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $this->createDocument($alpha, $slug, 'approved', reviewer: $reviewer);
        }

        // 2. CyberNet Solutions — IT Vendor, High risk, NDA missing + cybersecurity pending
        $cybernet = $this->createVendor([
            'name'                 => 'CyberNet Solutions Sdn Bhd',
            'registration_number'  => 'SSM-002-CYBER',
            'category'             => 'it_vendor',
            'risk_level'           => 'high',
            'contact_person'       => 'Priya Menon',
            'email'                => 'priya@cybernetsolutions.test',
            'country'              => 'MY',
            'status'               => 'under_review',
            'compliance_status'    => 'documents_missing',
            'compliance_score'     => 55,
            'assigned_reviewer_id' => $reviewer->id,
        ]);
        $this->createVendorUserAccount('vendor.cybernet@demo.test', 'Priya Menon', $cybernet, 'accepted');
        // 4 of 6 uploaded, NDA missing, cybersecurity under review
        foreach (['company_registration', 'tax_certificate', 'bank_verification', 'contract'] as $slug) {
            $this->createDocument($cybernet, $slug, 'approved', reviewer: $reviewer);
        }
        $this->createDocument($cybernet, 'cybersecurity_declaration', 'under_review');
        // NDA deliberately not uploaded — spec says "NDA missing"

        // 3. BuildPro Contractors — Contractor, Medium risk, Insurance expiring in 7 days
        $buildpro = $this->createVendor([
            'name'                 => 'BuildPro Contractors Sdn Bhd',
            'registration_number'  => 'SSM-003-BUILD',
            'category'             => 'contractor',
            'risk_level'           => 'medium',
            'contact_person'       => 'Raj Kumar',
            'email'                => 'raj@buildprocontractors.test',
            'country'              => 'MY',
            'status'               => 'expiring_soon',
            'compliance_status'    => 'expiring_soon',
            'compliance_score'     => 80,
            'assigned_reviewer_id' => $reviewer->id,
        ]);
        $this->createVendorUserAccount('vendor.buildpro@demo.test', 'Raj Kumar', $buildpro, 'accepted');
        foreach (['company_registration', 'contract'] as $slug) {
            $this->createDocument($buildpro, $slug, 'approved', reviewer: $reviewer);
        }
        $this->createDocument($buildpro, 'business_license', 'approved', reviewer: $reviewer, expiryDate: now()->addMonths(6)->toDateString());
        $this->createDocument($buildpro, 'safety_certificate', 'approved', reviewer: $reviewer, expiryDate: now()->addMonths(3)->toDateString());
        // Insurance expiring in 7 days — the critical scenario
        $this->createDocument($buildpro, 'insurance_certificate', 'expiring_soon', reviewer: $reviewer, expiryDate: now()->addDays(7)->toDateString());

        // 4. Noor Consulting — Consultant, Low risk, Bank document rejected
        $noor = $this->createVendor([
            'name'                 => 'Noor Consulting',
            'registration_number'  => 'ROB-004-NOOR',
            'category'             => 'consultant',
            'risk_level'           => 'low',
            'contact_person'       => 'Noor Aisyah',
            'email'                => 'noor@noorconsulting.test',
            'country'              => 'MY',
            'status'               => 'correction_required',
            'compliance_status'    => 'correction_required',
            'compliance_score'     => 60,
            'assigned_reviewer_id' => $reviewer->id,
        ]);
        $this->createVendorUserAccount('vendor.noor@demo.test', 'Noor Aisyah', $noor, 'accepted');
        foreach (['company_registration', 'tax_certificate', 'contract', 'nda'] as $slug) {
            $this->createDocument($noor, $slug, 'approved', reviewer: $reviewer);
        }
        // Bank verification rejected with comment
        $this->createDocument(
            $noor, 'bank_verification', 'rejected',
            reviewer: $reviewer,
            reviewComment: 'Account number is unclear in the uploaded image. Please reupload a clearer copy of your bank confirmation letter.',
        );

        // 5. SecureGate Systems — High-Risk Vendor, High risk, Multiple docs under review
        $securegate = $this->createVendor([
            'name'                 => 'SecureGate Systems Sdn Bhd',
            'registration_number'  => 'SSM-005-SECG',
            'category'             => 'high_risk',
            'risk_level'           => 'high',
            'contact_person'       => 'James Tan',
            'email'                => 'james@securegate.test',
            'country'              => 'MY',
            'status'               => 'under_review',
            'compliance_status'    => 'under_review',
            'compliance_score'     => 30,
            'assigned_reviewer_id' => $reviewer->id,
        ]);
        $this->createVendorUserAccount('vendor.securegate@demo.test', 'James Tan', $securegate, 'accepted');
        $this->createDocument($securegate, 'company_registration', 'approved', reviewer: $reviewer);
        // Several documents under review
        foreach (['tax_certificate', 'bank_verification', 'business_license', 'insurance_certificate', 'cybersecurity_declaration', 'contract', 'safety_certificate'] as $slug) {
            $this->createDocument($securegate, $slug, 'under_review');
        }
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function createVendor(array $data): Vendor
    {
        return Vendor::firstOrCreate(
            ['name' => $data['name']],
            $data,
        );
    }

    private function createVendorUserAccount(
        string $email,
        string $name,
        Vendor $vendor,
        string $invitationStatus = 'accepted',
    ): User {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make('password'),
                'role'     => 'vendor_user',
                'status'   => 'active',
            ],
        );

        VendorUser::firstOrCreate(
            ['user_id' => $user->id, 'vendor_id' => $vendor->id],
            [
                'invitation_status'       => $invitationStatus,
                'invitation_accepted_at'  => $invitationStatus === 'accepted' ? now() : null,
                'invitation_sent_at'      => now()->subDays(3),
            ],
        );

        return $user;
    }

    private function createDocument(
        Vendor $vendor,
        string $slug,
        string $status,
        ?User $reviewer = null,
        ?string $expiryDate = null,
        ?string $reviewComment = null,
    ): VendorDocument {
        $docTypeId = $this->docTypeIds[$slug] ?? null;
        if (! $docTypeId) {
            throw new \RuntimeException("DocumentType slug '{$slug}' not found. Run DocumentTypeSeeder first.");
        }

        // Use the first vendor user as the uploader, or create a fallback
        $uploader = $vendor->vendorUsers()->with('user')->first()?->user
            ?? User::where('role', 'compliance_admin')->first();

        return VendorDocument::firstOrCreate(
            ['vendor_id' => $vendor->id, 'document_type_id' => $docTypeId],
            [
                'uploaded_by'      => $uploader->id,
                'file_path'        => "demo/{$vendor->id}/{$slug}_v1.pdf",
                'original_filename' => "{$slug}.pdf",
                'mime_type'        => 'application/pdf',
                'file_size_kb'     => rand(100, 2000),
                'version_number'   => 1,
                'status'           => $status,
                'expiry_date'      => $expiryDate,
                'uploaded_at'      => now()->subDays(rand(1, 14)),
                'reviewed_by'      => $reviewer?->id,
                'reviewed_at'      => $reviewer ? now()->subDays(rand(0, 7)) : null,
                'review_comment'   => $reviewComment,
            ],
        );
    }
}
