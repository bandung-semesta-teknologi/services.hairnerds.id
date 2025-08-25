<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizResultStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'lesson_id' => 'required|exists:lessons,id',
            'answered' => 'nullable|integer|min:0',
            'correct_answers' => 'nullable|integer|min:0',
            'total_obtained_marks' => 'nullable|integer|min:0',
            'is_submitted' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
        ];
    }
}
