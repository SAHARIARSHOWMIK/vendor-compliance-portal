<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->company(),
            'registration_number' => strtoupper(fake()->bothify('SSM-###-???')),
            'category'          => fake()->randomElement(['general_supplier', 'it_vendor', 'contractor', 'consultant', 'high_risk']),
            'risk_level'        => fake()->randomElement(['low', 'medium', 'high']),
            'contact_person'    => fake()->name(),
            'email'             => fake()->companyEmail(),
            'phone'             => fake()->phoneNumber(),
            'address'           => fake()->address(),
            'country'           => 'MY',
            'status'            => 'draft',
            'compliance_score'  => 0,
        ];
    }

    public function fullyCompliant(): static
    {
        return $this->state([
            'status'            => 'fully_compliant',
            'compliance_status' => 'fully_compliant',
            'compliance_score'  => 100,
        ]);
    }

    public function suspended(): static
    {
        return $this->state([
            'status'            => 'suspended',
            'compliance_status' => 'suspended',
        ]);
    }

    public function itVendor(): static
    {
        return $this->state(['category' => 'it_vendor', 'risk_level' => 'high']);
    }
}
