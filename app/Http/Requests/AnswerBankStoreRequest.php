<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerBankStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => 'required|exists:questions,id',
            'answer' => 'required|string|max:255',
            'is_true' => 'nullable|boolean',
        ];
    }
}
