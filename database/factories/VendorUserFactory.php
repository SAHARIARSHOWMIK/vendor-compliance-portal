<?php

namespace Database\Factories;

use App\Models\VendorUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorUserFactory extends Factory
{
    protected $model = VendorUser::class;

    public function definition(): array
    {
        return [
            'user_id'                 => \App\Models\User::factory(),
            'vendor_id'               => \App\Models\Vendor::factory(),
            'role_within_vendor'      => 'primary_contact',
            'invitation_status'       => 'accepted',
            'invitation_token'        => null,
            'invitation_sent_at'      => now()->subDays(3),
            'invitation_accepted_at'  => now()->subDays(2),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'invitation_status'      => 'pending',
            'invitation_token'       => \Illuminate\Support\Str::random(64),
            'invitation_accepted_at' => null,
        ]);
    }
}
