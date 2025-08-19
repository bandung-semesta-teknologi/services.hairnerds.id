<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        $titles = [
            'Introduction to Barbering',
            'Basic Haircut Techniques',
            'Advanced Scissor Work',
            'Clipper Mastery',
            'Beard Trimming Fundamentals',
            'Hair Washing Techniques',
            'Customer Service Excellence',
            'Tool Maintenance',
            'Safety and Hygiene',
            'Business Skills for Barbers'
        ];

        $objectives = [
            'Learn the fundamental principles',
            'Master basic techniques',
            'Develop advanced skills',
            'Practice professional methods',
            'Understand safety protocols',
            'Build customer relationships',
            'Maintain professional standards'
        ];

        return [
            'course_id' => Course::factory(),
            'sequence' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->randomElement($titles),
            'objective' => $this->faker->optional(0.8)->randomElement($objectives),
        ];
    }
}
