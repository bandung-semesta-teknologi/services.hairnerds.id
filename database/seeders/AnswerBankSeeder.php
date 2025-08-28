<?php

namespace Database\Seeders;

use App\Models\AnswerBank;
use App\Models\Question;
use Illuminate\Database\Seeder;

class AnswerBankSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::all();

        if ($questions->isEmpty()) {
            $this->command->warn('No questions found. Skipping AnswerBank seeding.');
            return;
        }

        foreach ($questions as $question) {
            $answerCount = 3;

            $trueTargets = $question->type === 'multiple_choice'
                ? fake()->numberBetween(1, 2)
                : 1;

            $trueAssigned = 0;

            for ($i = 0; $i < $answerCount; $i++) {
                $shouldBeTrue = false;
                if ($trueAssigned < $trueTargets) {
                    $remainingSlots = $answerCount - $i;
                    $shouldBeTrue = $trueTargets - $trueAssigned >= $remainingSlots
                        ? true
                        : fake()->boolean(40);
                }

                if ($shouldBeTrue) {
                    $trueAssigned++;
                }

                AnswerBank::factory()->create([
                    'question_id' => $question->id,
                    'is_true' => $shouldBeTrue,
                ]);
            }

            if ($trueAssigned === 0) {
                $question->answerBanks()->first()->update(['is_true' => true]);
            }
        }
    }
}
