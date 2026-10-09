<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ConsultationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_reference' => 'BRV-'.Str::upper(Str::random(8)),
            'submission_key' => (string) Str::uuid(),
            'payload_hash' => hash('sha256', Str::random()),
            'consultation_type_id' => ConsultationType::factory()->published(),
            'type_snapshot' => 'Introductory call',
            'duration_minutes' => 30,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'summary' => fake()->paragraph(),
            'preferred_at_utc' => now()->addDays(3)->setTime(9, 0),
            'requested_timezone' => 'Asia/Beirut',
            'status' => AppointmentStatus::Requested,
            'policy_version' => 'test',
            'privacy_accepted_at' => now(),
        ];
    }
}
