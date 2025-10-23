<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqStoreRequest extends FormRequest
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
            'faqable_type' => ['required', 'string', Rule::in(['App\\Models\\Course', 'App\\Models\\Bootcamp'])],
            'faqable_id' => 'required|integer',
            'faqs' => 'required|array|min:1',
            'faqs.*.question' => 'required|string|max:500',
            'faqs.*.answer' => 'required|string|max:2000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $faqableType = $this->input('faqable_type');
            $faqableId = $this->input('faqable_id');

            if ($faqableType === 'App\\Models\\Course') {
                if (!\App\Models\Course::where('id', $faqableId)->exists()) {
                    $validator->errors()->add('faqable_id', 'The selected course does not exist.');
                }
            } elseif ($faqableType === 'App\\Models\\Bootcamp') {
                if (!\App\Models\Bootcamp::where('id', $faqableId)->exists()) {
                    $validator->errors()->add('faqable_id', 'The selected bootcamp does not exist.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'faqs.required' => 'Minimal harus ada 1 FAQ',
            'faqs.array' => 'FAQs harus berupa array',
            'faqs.min' => 'Minimal harus ada 1 FAQ',
            'faqs.*.question.required' => 'Setiap FAQ harus memiliki pertanyaan',
            'faqs.*.question.string' => 'Pertanyaan harus berupa teks',
            'faqs.*.question.max' => 'Pertanyaan maksimal 500 karakter',
            'faqs.*.answer.required' => 'Setiap FAQ harus memiliki jawaban',
            'faqs.*.answer.string' => 'Jawaban harus berupa teks',
            'faqs.*.answer.max' => 'Jawaban maksimal 2000 karakter',
        ];
    }
}
