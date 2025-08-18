<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $categories = [
            'Basic Haircut Techniques',
            'Advanced Styling',
            'Beard Grooming',
            'Hair Washing & Treatment',
            'Fade Techniques',
            'Business Skills',
            'Customer Service',
            'Scissor Techniques',
            'Clipper Mastery',
            'Hair Color & Highlights'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
        ];
    }
}
