<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseWithFaqStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_description' => 'required|string|max:1000',
            'description' => 'required|string',
            'requirements' => 'required|string|max:255',
            'category_ids' => 'required|array',
            'category_ids.*' => 'required',
            'instructor_ids' => 'required|array',
            'instructor_ids.*' => 'exists:users,id',
            'level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'lang' => 'required|string|max:50',
            'price' => 'required|integer|min:0',
            'is_highlight' => 'required|boolean',
            'status' => ['required', Rule::in(['draft', 'rejected', 'notpublished', 'published', 'takedown'])],
            'thumbnail' => 'nullable|image|max:2048',
            'faqs' => 'nullable|array',
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
