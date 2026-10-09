<?php

namespace Database\Factories;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Enquiry> */
class EnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_reference' => 'BRV-'.Str::upper(Str::random(8)),
            'submission_key' => (string) Str::uuid(),
            'payload_hash' => hash('sha256', Str::random()),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'enquiry_type' => EnquiryType::NewProduct,
            'message' => fake()->paragraph(),
            'status' => EnquiryStatus::New,
            'policy_version' => 'test',
            'privacy_accepted_at' => now(),
        ];
    }
}
