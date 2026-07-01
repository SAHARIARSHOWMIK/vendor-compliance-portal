<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => RoleName::ComplianceAdmin,
            'status' => 'active',
            'last_login_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => RoleName::SuperAdmin]);
    }

    public function complianceAdmin(): static
    {
        return $this->state(['role' => RoleName::ComplianceAdmin]);
    }

    public function reviewer(): static
    {
        return $this->state(['role' => RoleName::Reviewer]);
    }

    public function vendorUser(): static
    {
        return $this->state(['role' => RoleName::VendorUser]);
    }

    public function auditor(): static
    {
        return $this->state(['role' => RoleName::Auditor]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }
}
