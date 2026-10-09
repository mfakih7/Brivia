<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Correct-Horse-9!'),
            'remember_token' => Str::random(10),
            'role' => Role::ContentEditor,
            'is_active' => true,
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => Role::Owner]);
    }

    public function contentEditor(): static
    {
        return $this->state(['role' => Role::ContentEditor]);
    }

    public function operationsManager(): static
    {
        return $this->state(['role' => Role::OperationsManager]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false, 'deactivated_at' => now()]);
    }
}
