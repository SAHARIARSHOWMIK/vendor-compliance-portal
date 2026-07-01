<?php

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        return [
            'vendor_document_id' => VendorDocument::factory(),
            'vendor_id'          => Vendor::factory(),
            'document_type_id'   => DocumentType::factory(),
            'uploaded_by'        => User::factory(),
            'file_path'          => fake()->uuid() . '/document_v1.pdf',
            'original_filename'  => 'document_v1.pdf',
            'mime_type'          => 'application/pdf',
            'file_size_kb'       => fake()->numberBetween(100, 5000),
            'version_number'     => 1,
            'status_at_snapshot' => 'uploaded',
            'expiry_date'        => null,
            'change_note'        => null,
            'uploaded_at'        => now()->subHours(2),
        ];
    }
}
