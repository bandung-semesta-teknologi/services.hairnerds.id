<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerBankUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => 'sometimes|required|exists:questions,id',
            'answer' => 'sometimes|required|string|max:255',
            'is_true' => 'nullable|boolean',
        ];
    }
}
