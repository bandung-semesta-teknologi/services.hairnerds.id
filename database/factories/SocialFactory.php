<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialFactory extends Factory
{
    public function definition(): array
    {
        $types = ['instagram', 'facebook', 'twitter', 'linkedin', 'youtube', 'tiktok'];
        $type = fake()->randomElement($types);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'url' => $this->generateUrlByType($type),
        ];
    }

    private function generateUrlByType(string $type): string
    {
        $username = fake()->userName();

        return match($type) {
            'instagram' => "https://instagram.com/{$username}",
            'facebook' => "https://facebook.com/{$username}",
            'twitter' => "https://twitter.com/{$username}",
            'linkedin' => "https://linkedin.com/in/{$username}",
            'youtube' => "https://youtube.com/@{$username}",
            'tiktok' => "https://tiktok.com/@{$username}",
            default => fake()->url(),
        };
    }

    public function instagram(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'instagram',
            'url' => "https://instagram.com/" . fake()->userName(),
        ]);
    }

    public function facebook(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'facebook',
            'url' => "https://facebook.com/" . fake()->userName(),
        ]);
    }

    public function twitter(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'twitter',
            'url' => "https://twitter.com/" . fake()->userName(),
        ]);
    }
}
