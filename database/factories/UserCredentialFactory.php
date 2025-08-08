<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserCredential>
 */
class UserCredentialFactory extends Factory
{
    protected static ?string $type;
    protected static ?string $identifier;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => static::$type ??= 'email',
            'identifier' => static::$identifier ??= fake()->safeEmail(),
            'verified_at' => now(),
        ];
    }

    /**
     * Indicate that the model's credential use type of email credential.
     */
    public function emailCredential(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'email',
                'identifier' => $attributes['identifier'] ?? fake()->safeEmail(),
            ];
        });
    }

    /**
     * Indicate that the model's credential use type of phone credential.
     */
    public function phoneCredential(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'phone',
            'identifier' => fake()->phoneNumber(),
        ]);
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
