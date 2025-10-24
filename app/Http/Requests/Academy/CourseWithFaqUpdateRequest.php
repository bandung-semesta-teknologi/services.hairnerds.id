<?php

namespace App\Http\Requests\Academy;

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
            'category_ids.*' => 'required',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('category_ids')) {
                foreach ($this->category_ids as $key => $category) {
                    if (!is_numeric($category) && !is_string($category)) {
                        $validator->errors()->add(
                            "category_ids.{$key}",
                            'Category must be either an ID or a name'
                        );
                    }

                    if (is_string($category) && strlen(trim($category)) === 0) {
                        $validator->errors()->add(
                            "category_ids.{$key}",
                            'Category name cannot be empty'
                        );
                    }

                    if (is_string($category) && strlen($category) > 255) {
                        $validator->errors()->add(
                            "category_ids.{$key}",
                            'Category name cannot exceed 255 characters'
                        );
                    }
                }
            }
        });
    }
}
