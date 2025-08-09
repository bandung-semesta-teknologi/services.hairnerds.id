<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserCredential>
 */
class UserCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'email',
            'identifier' => fake()->safeEmail(),
            'verified_at' => now(),
        ];
    }

    /**
     * Indicate that the model's credential use type of email credential.
     */
    public function emailCredential(?string $identifier = null): static
    {
        return $this->state(function (array $attributes) use ($identifier) {
            return [
                'type' => 'email',
                'identifier' => $identifier ?? fake()->unique()->safeEmail(),
            ];
        });
    }

    /**
     * Indicate that the model's credential use type of phone credential.
     */
    public function phoneCredential(?string $identifier = null): static
    {
        return $this->state(function (array $attributes) use ($identifier) {
            return [
                'type' => 'phone',
                'identifier' => $identifier ?? fake()->unique()->phoneNumber(),
            ];
        });
    }

    /**
     * Indicate that the model's credential should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'verified_at' => null,
        ]);
    }
}
