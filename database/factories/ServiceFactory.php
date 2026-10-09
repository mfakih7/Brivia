<?php

namespace Database\Factories;

use App\Enums\IconKey;
use App\Enums\PublicationStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(12),
            'body' => fake()->paragraph(),
            'deliverables' => ['Assessment', 'Recommendations'],
            'engagement_steps' => ['Discovery call', 'Proposal'],
            'icon_key' => IconKey::Lightbulb,
            'sort_order' => 0,
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()]);
    }
}
