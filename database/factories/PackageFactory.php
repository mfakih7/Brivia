<?php

namespace Database\Factories;

use App\Enums\PriceMode;
use App\Enums\PublicationStatus;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Package> */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'summary' => fake()->sentence(10),
            'features' => ['Planning', 'Design', 'Development'],
            'exclusions' => ['Hosting'],
            'price_mode' => PriceMode::Quote,
            'price_amount' => null,
            'currency' => 'USD',
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()]);
    }
}
