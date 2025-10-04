<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseFaqStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'faqs' => 'required|array|min:1',
            'faqs.*.question' => 'required|string|max:500',
            'faqs.*.answer' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'faqs.required' => 'At least one FAQ is required',
            'faqs.array' => 'FAQs must be an array',
            'faqs.min' => 'At least one FAQ is required',
            'faqs.*.question.required' => 'Each FAQ must have a question',
            'faqs.*.question.string' => 'Question must be a string',
            'faqs.*.question.max' => 'Question must not exceed 500 characters',
            'faqs.*.answer.required' => 'Each FAQ must have an answer',
            'faqs.*.answer.string' => 'Answer must be a string',
            'faqs.*.answer.max' => 'Answer must not exceed 2000 characters',
        ];
    }
}
