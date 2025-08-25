<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = Quiz::all();

        if ($quizzes->isEmpty()) {
            $this->command->warn('No quizzes found. Skipping Question seeding.');
            return;
        }

        foreach ($quizzes as $quiz) {
            $questionCount = rand(5, 10);
            $types = ['single_choice', 'multiple_choice', 'fill_blank'];

            for ($i = 0; $i < $questionCount; $i++) {
                $selectedType = fake()->randomElement($types);

                Question::factory()->create([
                    'quiz_id' => $quiz->id,
                    'type' => $selectedType,
                ]);
            }
        }
    }
}
