<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BootcampWithFaqUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => 'sometimes|required|exists:users,id',
            'title' => 'sometimes|required|string|max:255',
            'start_at' => 'sometimes|required|date',
            'end_at' => 'sometimes|required|date|after:start_at',
            'seat' => 'sometimes|required|integer|min:1',
            'seat_available' => 'nullable|integer|min:0',
            'seat_blocked' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|image|max:2048',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'required',
            'status' => ['sometimes', Rule::in(['draft', 'publish', 'unpublish', 'rejected'])],
            'price' => 'nullable|integer|min:0',
            'location' => 'sometimes|required|string|max:255',
            'contact_person' => 'sometimes|required|string|max:255',
            'url_location' => 'nullable|string|url',
            'verified_at' => 'nullable|date',
            'faqs' => 'nullable|array',
            'faqs.*.id' => 'nullable|exists:faqs,id',
            'faqs.*.question' => 'required|string|max:500',
            'faqs.*.answer' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'end_at.after' => 'End date must be after start date',
            'faqs.*.question.required' => 'Question is required for all FAQs',
            'faqs.*.answer.required' => 'Answer is required for all FAQs',
            'faqs.*.question.max' => 'Question cannot exceed 500 characters',
            'faqs.*.answer.max' => 'Answer cannot exceed 2000 characters',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('seat') && $this->has('seat_available')) {
                if ($this->seat_available > $this->seat) {
                    $validator->errors()->add('seat_available', 'Available seats cannot exceed total seats');
                }
            }

            if ($this->has('seat') && $this->has('seat_blocked')) {
                $totalUsed = ($this->seat_available ?? 0) + ($this->seat_blocked ?? 0);
                if ($totalUsed > $this->seat) {
                    $validator->errors()->add('seat_blocked', 'Total available and blocked seats cannot exceed total seats');
                }
            }

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
