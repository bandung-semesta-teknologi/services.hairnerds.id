<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseWithFaqUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string|max:255',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
            'instructor_ids' => 'sometimes|array',
            'instructor_ids.*' => 'exists:users,id',
            'level' => ['sometimes', 'required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'lang' => 'sometimes|required|string|max:50',
            'price' => 'nullable|integer|min:0',
            'is_highlight' => 'nullable|boolean',
            'status' => ['sometimes', Rule::in(['draft', 'rejected', 'notpublished', 'published', 'takedown'])],
            'thumbnail' => 'nullable|image|max:2048',

            'faqs' => 'nullable|array',
			'faqs.*.id' => 'nullable|exists:faqs,id',
            'faqs.*.question' => 'required|string|max:500',
            'faqs.*.answer' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'faqs.*.question.required' => 'Question is required for all FAQs',
            'faqs.*.answer.required' => 'Answer is required for all FAQs',
            'faqs.*.question.max' => 'Question cannot exceed 500 characters',
            'faqs.*.answer.max' => 'Answer cannot exceed 2000 characters',
        ];
    }
}
