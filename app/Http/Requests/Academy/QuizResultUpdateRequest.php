<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class QuizResultUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answered' => 'sometimes|integer|min:0',
            'correct_answers' => 'sometimes|integer|min:0',
            'total_obtained_marks' => 'sometimes|integer|min:0',
            'is_submitted' => 'sometimes|boolean',
            'finished_at' => 'sometimes|nullable|date|after_or_equal:started_at',
        ];
    }

    public function messages(): array
    {
        return [
            'answered.integer' => 'Answered must be a number',
            'answered.min' => 'Answered must be at least 0',
            'correct_answers.integer' => 'Correct answers must be a number',
            'correct_answers.min' => 'Correct answers must be at least 0',
            'total_obtained_marks.integer' => 'Total obtained marks must be a number',
            'total_obtained_marks.min' => 'Total obtained marks must be at least 0',
            'is_submitted.boolean' => 'Is submitted must be true or false',
            'finished_at.date' => 'Finished at must be a valid date',
            'finished_at.after_or_equal' => 'Finished at must be after or equal to started at',
        ];
    }
}
