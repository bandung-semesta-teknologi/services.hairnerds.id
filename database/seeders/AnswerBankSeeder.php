<?php

namespace Database\Seeders;

use App\Models\AnswerBank;
use App\Models\Question;
use Illuminate\Database\Seeder;

class AnswerBankSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::take(20)->get();

        if ($questions->isEmpty()) {
            $questions = Question::factory()->count(10)->create();
        }

        foreach ($questions as $question) {
            $answerCount = match($question->type) {
                'single_choice' => 4,
                'multiple_choice' => rand(4, 6),
                'fill_blank' => 1,
                default => 4
            };

            for ($i = 0; $i < $answerCount; $i++) {
                $isCorrect = match($question->type) {
                    'single_choice' => $i === 0,
                    'multiple_choice' => $this->faker()->boolean(40),
                    'fill_blank' => true,
                    default => $i === 0
                };

                AnswerBank::factory()->create([
                    'question_id' => $question->id,
                    'is_true' => $isCorrect,
                ]);
            }

            if ($question->type === 'multiple_choice') {
                $correctCount = $question->answerBanks()->where('is_true', true)->count();
                if ($correctCount === 0) {
                    $question->answerBanks()->first()->update(['is_true' => true]);
                }
            }
        }
    }

    private function faker()
    {
        return \Faker\Factory::create();
    }
}
