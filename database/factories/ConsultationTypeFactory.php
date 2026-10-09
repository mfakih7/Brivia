<?php

namespace Database\Factories;

use App\Enums\ConsultationPricingMode;
use App\Enums\PublicationStatus;
use App\Models\ConsultationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ConsultationType> */
class ConsultationTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'duration_minutes' => 30,
            'pricing_mode' => ConsultationPricingMode::Free,
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()]);
    }
}
