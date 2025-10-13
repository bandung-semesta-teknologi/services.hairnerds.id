<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address' => fake()->address(),
            'avatar' => null,
            'short_biography' => fake()->sentence(10),
            'biography' => fake()->paragraphs(3, true),
            'skills' => fake()->randomElements(
                ['Laravel', 'PHP', 'JavaScript', 'React', 'Vue', 'Node.js', 'MySQL', 'PostgreSQL', 'Docker', 'AWS'],
                rand(3, 6)
            ),
            'date_of_birth' => now()->subYears(rand(17, 25)),
        ];
    }
}
