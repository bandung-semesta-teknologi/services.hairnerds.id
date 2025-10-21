<?php

namespace App\Services;

use App\Models\Question;
use App\Models\AnswerBank;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QuizValidationService
{
    public function validateQuizSubmission(array $answers): array
    {
        $result = [
            'answered' => 0,
            'correct_answers' => 0,
            'total_obtained_marks' => 0,
            'is_submitted' => true
        ];

        foreach ($answers as $answer) {
            $questionId = $answer['question_id'];
            $questionType = $answer['type'];
            $submittedAnswers = $answer['answers'];

            $question = Question::with('answerBanks')->find($questionId);

            if (!$question) {
                Log::warning("Question not found: {$questionId}");
                continue;
            }

            $result['answered']++;

            $isCorrect = $this->validateAnswer($question, $questionType, $submittedAnswers);

            if ($isCorrect) {
                $result['correct_answers']++;
                $result['total_obtained_marks'] += $question->score ?? 0;
            }
        }

        return $result;
    }

    private function validateAnswer(Question $question, string $type, $submittedAnswers): bool
    {
        switch ($type) {
            case 'single_choice':
                return $this->validateSingleChoice($question, $submittedAnswers);

            case 'multiple_choice':
                return $this->validateMultipleChoice($question, $submittedAnswers);

            case 'fill_blank':
                return $this->validateFillBlank($question, $submittedAnswers);

            default:
                return false;
        }
    }

    private function validateSingleChoice(Question $question, $submittedAnswer): bool
    {
        $correctAnswer = $question->answerBanks
            ->where('is_true', true)
            ->first();

        if (!$correctAnswer) {
            return false;
        }

        return (int) $submittedAnswer === $correctAnswer->id;
    }

    private function validateMultipleChoice(Question $question, array $submittedAnswers): bool
    {
        $correctAnswerIds = $question->answerBanks
            ->where('is_true', true)
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        $submittedAnswerIds = collect($submittedAnswers)
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        return $correctAnswerIds === $submittedAnswerIds;
    }

    private function validateFillBlank(Question $question, string $submittedAnswer): bool
    {
        $correctAnswers = $question->answerBanks
            ->where('is_true', true)
            ->pluck('answer')
            ->map(fn($answer) => trim(strtolower($answer)))
            ->toArray();

        $submittedAnswerNormalized = trim(strtolower($submittedAnswer));

        return in_array($submittedAnswerNormalized, $correctAnswers);
    }
}
