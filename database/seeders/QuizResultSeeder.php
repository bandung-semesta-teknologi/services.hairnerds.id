<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizResultSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'student')->take(10)->get();
        $quizzes = Quiz::with('lesson')->take(15)->get();

        if ($users->isEmpty()) {
            $users = User::factory()->count(8)->create(['role' => 'student']);
        }

        if ($quizzes->isEmpty()) {
            $quizzes = Quiz::factory()->count(10)->create();
            $quizzes->load('lesson');
        }

        foreach ($users as $user) {
            $userQuizzes = $quizzes->random(rand(3, 8));

            foreach ($userQuizzes as $quiz) {
                $totalQuestions = $quiz->questions()->count() ?: rand(5, 15);
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
                ]);
            }
        }

        QuizResult::factory()->submitted()->count(15)->create();
        QuizResult::factory()->inProgress()->count(8)->create();
        QuizResult::factory()->passed()->count(12)->create();
    }
}
