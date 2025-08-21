<?php

namespace Database\Factories;

use App\Models\Course;
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
            'Modern Barbering Fundamentals',
            'Classic Scissor Over Comb',
            'Skin Fade Mastery Course'
        ];

        return [
            'title' => $this->faker->unique()->randomElement($titles),
            'short_description' => $this->faker->paragraph(2),
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => implode(', ', $this->faker->words(3)),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'lang' => $this->faker->randomElement(['english', 'indonesian', 'spanish']),
            'price' => $this->faker->numberBetween(0, 9999999),
            'status' => $this->faker->randomElement(['draft', 'rejected', 'notpublished', 'published', 'takedown']),
            'thumbnail' => $this->faker->optional(0.7)->imageUrl(640, 480, 'education'),
            'verified_at' => null,
        ];
    }

    public function verified()
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => now(),
        ]);
    }

    public function published()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function draft()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function notpublished()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'notpublished',
        ]);
    }

    public function takedown()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'takedown',
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

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
