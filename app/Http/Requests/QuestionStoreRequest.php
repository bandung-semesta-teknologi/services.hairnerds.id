<?php

namespace App\Http\Requests;

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
        ];
    }
}
