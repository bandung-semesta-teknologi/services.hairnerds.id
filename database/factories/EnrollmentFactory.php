<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'enrolled_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'finished_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'quiz_attempts' => $this->faker->numberBetween(0, 5),
        ];
    }

    public function finished()
    {
        return $this->state(fn (array $attributes) => [
            'finished_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function active()
    {
        return $this->state(fn (array $attributes) => [
            'finished_at' => null,
        ]);
    }
}
