<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\VendorCategoryRequirement;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Seeds the document_types master table and the
     * vendor_category_requirements matrix from spec sections 8 and 9.
     *
     * Idempotent: uses firstOrCreate so re-running does not duplicate rows.
     */
    public function run(): void
    {
        $types = $this->documentTypes();
        $bySlug = [];

        foreach ($types as $data) {
            $dt = DocumentType::firstOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
            $bySlug[$dt->slug] = $dt->id;
        }

        // --- Vendor category × document type requirement matrix ---
        // From spec section 9.

        $requirements = [
            // General Supplier: registration, tax, bank, contract
            ['category' => 'general_supplier', 'slug' => 'company_registration', 'level' => 'required'],
            ['category' => 'general_supplier', 'slug' => 'tax_certificate',       'level' => 'required'],
            ['category' => 'general_supplier', 'slug' => 'bank_verification',     'level' => 'required'],
            ['category' => 'general_supplier', 'slug' => 'contract',              'level' => 'required'],

            // IT Vendor: registration, tax, bank, contract, NDA, cybersecurity
            ['category' => 'it_vendor', 'slug' => 'company_registration', 'level' => 'required'],
            ['category' => 'it_vendor', 'slug' => 'tax_certificate',       'level' => 'required'],
            ['category' => 'it_vendor', 'slug' => 'bank_verification',     'level' => 'required'],
            ['category' => 'it_vendor', 'slug' => 'contract',              'level' => 'required'],
            ['category' => 'it_vendor', 'slug' => 'nda',                   'level' => 'required'],
            ['category' => 'it_vendor', 'slug' => 'cybersecurity_declaration', 'level' => 'required'],

            // Contractor: registration, business license, insurance, safety, contract
            ['category' => 'contractor', 'slug' => 'company_registration', 'level' => 'required'],
            ['category' => 'contractor', 'slug' => 'business_license',     'level' => 'required'],
            ['category' => 'contractor', 'slug' => 'insurance_certificate', 'level' => 'required', 'expiry' => true],
            ['category' => 'contractor', 'slug' => 'safety_certificate',   'level' => 'required', 'expiry' => true],
            ['category' => 'contractor', 'slug' => 'contract',             'level' => 'required'],

            // Consultant: identity/company proof, bank, tax, contract, NDA
            ['category' => 'consultant', 'slug' => 'company_registration', 'level' => 'required'],
            ['category' => 'consultant', 'slug' => 'bank_verification',    'level' => 'required'],
            ['category' => 'consultant', 'slug' => 'tax_certificate',      'level' => 'required'],
            ['category' => 'consultant', 'slug' => 'contract',             'level' => 'required'],
            ['category' => 'consultant', 'slug' => 'nda',                  'level' => 'required'],

            // High-Risk: registration, tax, bank, business license, insurance,
            // compliance declaration, contract, risk approval
            ['category' => 'high_risk', 'slug' => 'company_registration',      'level' => 'required'],
            ['category' => 'high_risk', 'slug' => 'tax_certificate',           'level' => 'required'],
            ['category' => 'high_risk', 'slug' => 'bank_verification',         'level' => 'required'],
            ['category' => 'high_risk', 'slug' => 'business_license',          'level' => 'required'],
            ['category' => 'high_risk', 'slug' => 'insurance_certificate',     'level' => 'required', 'expiry' => true],
            ['category' => 'high_risk', 'slug' => 'cybersecurity_declaration', 'level' => 'required'],
            ['category' => 'high_risk', 'slug' => 'contract',                  'level' => 'required'],
            ['category' => 'high_risk', 'slug' => 'safety_certificate',        'level' => 'required', 'expiry' => true],
        ];

        foreach ($requirements as $req) {
            $documentTypeId = $bySlug[$req['slug']] ?? null;
            if (! $documentTypeId) {
                continue;
            }

            VendorCategoryRequirement::firstOrCreate(
                [
                    'vendor_category'  => $req['category'],
                    'document_type_id' => $documentTypeId,
                ],
                [
                    'requirement_level' => $req['level'],
                    'expiry_required'   => $req['expiry'] ?? false,
                ],
            );
        }
    }

    private function documentTypes(): array
    {
        return [
            [
                'name'                    => 'Company Registration',
                'slug'                    => 'company_registration',
                'description'             => 'SSM certificate or business registration document verifying the vendor is legally registered.',
                'category'                => 'company_registration',
                'requires_expiry_date'    => false,
                'is_mandatory_by_default' => true,
                'allowed_file_types'      => 'pdf,jpg,jpeg,png',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 1,
            ],
            [
                'name'                    => 'Tax Certificate',
                'slug'                    => 'tax_certificate',
                'description'             => 'Tax registration certificate, SST/GST certificate, or tax identification proof.',
                'category'                => 'tax',
                'requires_expiry_date'    => false,
                'is_mandatory_by_default' => true,
                'allowed_file_types'      => 'pdf,jpg,jpeg,png',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 2,
            ],
            [
                'name'                    => 'Bank Verification Document',
                'slug'                    => 'bank_verification',
                'description'             => 'Bank account confirmation letter, bank statement header, or cancelled cheque for payment verification.',
                'category'                => 'bank_verification',
                'requires_expiry_date'    => false,
                'is_mandatory_by_default' => true,
                'allowed_file_types'      => 'pdf,jpg,jpeg,png',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 3,
            ],
            [
                'name'                    => 'Business License',
                'slug'                    => 'business_license',
                'description'             => 'Operating license, industry-specific permit, or local authority license.',
                'category'                => 'business_license',
                'requires_expiry_date'    => true,
                'is_mandatory_by_default' => false,
                'allowed_file_types'      => 'pdf,jpg,jpeg,png',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 4,
            ],
            [
                'name'                    => 'Insurance Certificate',
                'slug'                    => 'insurance_certificate',
                'description'             => 'Public liability, professional indemnity, or worker compensation insurance certificate.',
                'category'                => 'insurance',
                'requires_expiry_date'    => true,
                'is_mandatory_by_default' => false,
                'allowed_file_types'      => 'pdf,jpg,jpeg,png',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 5,
            ],
            [
                'name'                    => 'Contract / Agreement',
                'slug'                    => 'contract',
                'description'             => 'Vendor agreement, service contract, or procurement contract establishing the legal relationship.',
                'category'                => 'contract',
                'requires_expiry_date'    => true,
                'is_mandatory_by_default' => true,
                'allowed_file_types'      => 'pdf,doc,docx',
                'max_file_size_kb'        => 20480,
                'sort_order'              => 6,
            ],
            [
                'name'                    => 'NDA / Confidentiality Agreement',
                'slug'                    => 'nda',
                'description'             => 'Non-disclosure agreement protecting company information shared with the vendor.',
                'category'                => 'nda',
                'requires_expiry_date'    => false,
                'is_mandatory_by_default' => false,
                'allowed_file_types'      => 'pdf,doc,docx',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 7,
            ],
            [
                'name'                    => 'Safety / Compliance Certificate',
                'slug'                    => 'safety_certificate',
                'description'             => 'Safety compliance certificate, ISO certificate, or environmental compliance certificate.',
                'category'                => 'safety_compliance',
                'requires_expiry_date'    => true,
                'is_mandatory_by_default' => false,
                'allowed_file_types'      => 'pdf,jpg,jpeg,png',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 8,
            ],
            [
                'name'                    => 'Cybersecurity Declaration',
                'slug'                    => 'cybersecurity_declaration',
                'description'             => 'Cybersecurity compliance declaration required for IT vendors and high-risk vendors.',
                'category'                => 'safety_compliance',
                'requires_expiry_date'    => false,
                'is_mandatory_by_default' => false,
                'allowed_file_types'      => 'pdf,doc,docx',
                'max_file_size_kb'        => 10240,
                'sort_order'              => 9,
            ],
        ];
    }
}
