<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizResultFactory extends Factory
{
    protected $model = QuizResult::class;

    public function definition(): array
    {
        $totalQuestions = $this->faker->numberBetween(5, 20);
        $answered = $this->faker->numberBetween(1, $totalQuestions);
        $correctAnswers = $this->faker->numberBetween(0, $answered);
        $totalMarks = $correctAnswers * $this->faker->numberBetween(5, 10);
        $isSubmitted = $this->faker->boolean(80);

        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $finishedAt = $isSubmitted ?
            $this->faker->dateTimeBetween($startedAt, 'now') :
            null;

        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'lesson_id' => Lesson::factory(),
            'answered' => $answered,
            'correct_answers' => $correctAnswers,
            'total_obtained_marks' => $totalMarks,
            'is_submitted' => $isSubmitted,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ];
    }

    public function submitted()
    {
        return $this->state(function (array $attributes) {
            $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');

            return [
                'is_submitted' => true,
                'started_at' => $startedAt,
                'finished_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            ];
        });
    }

    public function inProgress()
    {
        return $this->state(fn (array $attributes) => [
            'is_submitted' => false,
            'finished_at' => null,
        ]);
    }

    public function passed()
    {
        return $this->state(fn (array $attributes) => [
            'correct_answers' => $this->faker->numberBetween(8, 15),
            'total_obtained_marks' => $this->faker->numberBetween(80, 150),
            'is_submitted' => true,
        ]);
    }
}
