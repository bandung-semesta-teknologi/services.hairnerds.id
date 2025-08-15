<?php

namespace Database\Factories;

use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseCategoryFactory extends Factory
{
    protected $model = CourseCategory::class;

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
            'name' => $this->faker->randomElement($categories),
        ];
    }
}
