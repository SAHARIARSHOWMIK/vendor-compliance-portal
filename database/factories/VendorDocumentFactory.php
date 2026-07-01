<?php

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorDocumentFactory extends Factory
{
    protected $model = VendorDocument::class;

    public function definition(): array
    {
        return [
            'vendor_id'         => Vendor::factory(),
            'document_type_id'  => DocumentType::factory(),
            'uploaded_by'       => User::factory(),
            'file_path'         => fake()->uuid() . '/document.pdf',
            'original_filename' => 'document.pdf',
            'mime_type'         => 'application/pdf',
            'file_size_kb'      => fake()->numberBetween(100, 5000),
            'version_number'    => 1,
            'status'            => 'uploaded',
            'expiry_date'       => null,
            'uploaded_at'       => now(),
            'reviewed_at'       => null,
            'reviewed_by'       => null,
            'review_comment'    => null,
            'notes'             => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'         => 'approved',
            'reviewed_at'    => now(),
            'reviewed_by'    => User::factory(),
        ]);
    }

    public function rejected(?string $comment = null): static
    {
        return $this->state(fn (array $attrs) => [
            'status'         => 'rejected',
            'reviewed_at'    => now(),
            'reviewed_by'    => User::factory(),
            'review_comment' => $comment ?? 'Document does not meet requirements.',
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state([
            'status'      => 'expiring_soon',
            'expiry_date' => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status'      => 'expired',
            'expiry_date' => now()->subDays(1),
        ]);
    }
}
