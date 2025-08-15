<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $titles = [
            'Basic Men\'s Haircut Techniques',
            'Advanced Fade Mastery',
            'Professional Beard Styling',
            'Hair Washing and Treatment',
            'Scissor Cutting Methods',
            'Clipper Techniques for Beginners',
            'Hair Color and Highlights',
        ];

        return [
            'title' => $this->faker->randomElement($titles),
            'short_description' => $this->faker->paragraph(2),
            'description' => $this->faker->paragraphs(3, true),
            'what_will_learn' => implode('. ', $this->faker->sentences(4)),
            'requirements' => implode('. ', $this->faker->sentences(2)),
            'category_id' => $this->faker->numberBetween(1, 4),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => 'english',
            'enable_drip_content' => $this->faker->boolean(),
            'price' => $this->faker->randomFloat(2, 29.99, 199.99),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
        ];
    }

    public function published()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function beginner()
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'beginner',
        ]);
    }

    public function intermediate()
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'intermediate',
        ]);
    }

    public function advanced()
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'advanced',
        ]);
    }
}
