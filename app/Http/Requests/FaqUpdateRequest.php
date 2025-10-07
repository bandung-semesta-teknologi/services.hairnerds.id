<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mappedType = null;
        $inputType = $this->input('type');

        if (is_string($inputType)) {
            $normalized = strtolower(trim($inputType));
            if ($normalized === 'course') {
                $mappedType = 'App\\Models\\Course';
            } elseif ($normalized === 'bootcamp') {
                $mappedType = 'App\\Models\\Bootcamp';
            }
        }

        $idReferens = $this->input('id_referens');

        $merge = [];
        if ($mappedType && !$this->has('faqable_type')) {
            $merge['faqable_type'] = $mappedType;
        }
        if (!is_null($idReferens) && !$this->has('faqable_id')) {
            $merge['faqable_id'] = $idReferens;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'question' => 'sometimes|required|string|max:500',
            'answer' => 'sometimes|required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Pertanyaan wajib diisi',
            'question.string' => 'Pertanyaan harus berupa teks',
            'question.max' => 'Pertanyaan maksimal 500 karakter',
            'answer.required' => 'Jawaban wajib diisi',
            'answer.string' => 'Jawaban harus berupa teks',
            'answer.max' => 'Jawaban maksimal 2000 karakter',
        ];
    }
}
