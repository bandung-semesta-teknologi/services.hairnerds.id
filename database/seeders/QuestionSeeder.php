<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = Quiz::take(10)->get();

        if ($quizzes->isEmpty()) {
            $quizzes = Quiz::factory()->count(5)->create();
        }

        foreach ($quizzes as $quiz) {
            $questionCount = rand(5, 15);
            $types = ['single_choice', 'multiple_choice', 'fill_blank'];

            for ($i = 0; $i < $questionCount; $i++) {
                $selectedType = $this->faker()->randomElement($types);

                Question::factory()->create([
                    'quiz_id' => $quiz->id,
                    'type' => $selectedType,
                ]);
            }
        }
    }

    private function faker()
    {
        return \Faker\Factory::create();
    }
}
