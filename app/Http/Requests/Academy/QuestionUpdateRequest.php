<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_id' => 'sometimes|required|exists:quizzes,id',
            'type' => ['sometimes', 'required', Rule::in(['single_choice', 'multiple_choice', 'fill_blank'])],
            'question' => 'sometimes|required|string',
            'score' => 'nullable|integer|min:0',
        ];
    }
}
