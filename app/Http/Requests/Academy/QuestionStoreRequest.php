<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_id' => 'required|exists:quizzes,id',
            'type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'fill_blank'])],
            'question' => 'required|string',
            'score' => 'nullable|integer|min:0',
            'answers' => 'nullable|array|min:1',
            'answers.*.answer' => 'required_with:answers|string|max:255',
            'answers.*.is_true' => 'required_with:answers|boolean',
        ];
    }
}
