<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamMember> */
class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role_title' => 'Co-founder',
            'biography' => fake()->paragraph(),
            'skills' => ['Laravel', 'Project management'],
            'years_experience' => 10,
            'social_links' => [],
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()]);
    }
}
