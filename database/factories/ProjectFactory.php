<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\WorkOrigin;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'category_id' => ProjectCategory::factory(),
            'summary' => fake()->sentence(14),
            'work_origin' => WorkOrigin::Concept,
            'problem' => fake()->paragraph(),
            'approach' => fake()->paragraph(),
            'solution' => fake()->paragraph(),
            'technologies' => ['Laravel', 'MySQL'],
            'permission_confirmed' => true,
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()]);
    }
}
