<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizResultSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'student')->get();
        $quizzes = Quiz::with('lesson')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No student users found. Skipping QuizResult seeding.');
            return;
        }

        if ($quizzes->isEmpty()) {
            $this->command->warn('No quizzes found. Skipping QuizResult seeding.');
            return;
        }

        foreach ($users as $user) {
            $userQuizzes = $quizzes->random(min(rand(2, 5), $quizzes->count()));

            foreach ($userQuizzes as $quiz) {
                $totalQuestions = $quiz->questions()->count() ?: rand(5, 10);
                $answered = rand(1, $totalQuestions);
                $correctAnswers = rand(0, $answered);
                $pointsPerQuestion = $quiz->total_marks ?
                    intval($quiz->total_marks / $totalQuestions) : rand(5, 10);

                QuizResult::factory()->create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                    'lesson_id' => $quiz->lesson_id,
                    'answered' => $answered,
                    'correct_answers' => $correctAnswers,
                    'total_obtained_marks' => $correctAnswers * $pointsPerQuestion,
                    'is_submitted' => fake()->boolean(80),
                ]);
            }
        }
    }
}
