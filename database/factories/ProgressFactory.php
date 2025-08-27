<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressFactory extends Factory
{
    protected $model = Progress::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'lesson_id' => Lesson::factory(),
            'is_completed' => false,
            'score' => null,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ];
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
            'score' => $this->faker->numberBetween(70, 100),
        ]);
    }

    public function incomplete()
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
            'score' => null,
        ]);
    }
}
