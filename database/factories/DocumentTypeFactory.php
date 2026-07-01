<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name'                    => ucwords($name),
            'slug'                    => Str::slug($name) . '_' . fake()->unique()->randomNumber(4),
            'description'             => fake()->sentence(),
            'category'                => fake()->randomElement([
                'company_registration', 'tax', 'bank_verification',
                'business_license', 'insurance', 'contract', 'nda', 'safety_compliance',
            ]),
            'requires_expiry_date'    => false,
            'is_mandatory_by_default' => true,
            'allowed_file_types'      => 'pdf,jpg,jpeg,png',
            'max_file_size_kb'        => 10240,
            'is_active'               => true,
            'sort_order'              => 0,
        ];
    }

    public function requiresExpiry(): static
    {
        return $this->state(['requires_expiry_date' => true]);
    }
}
