<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BootcampStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
            'seat' => 'required|integer|min:1',
            'seat_available' => 'nullable|integer|min:0|lte:seat',
            'seat_blocked' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|image|max:2048',
            'category_ids' => 'required|array',
            'category_ids.*' => 'required',
            'status' => ['nullable', Rule::in(['draft', 'publish', 'unpublish', 'rejected'])],
            'price' => 'nullable|integer|min:0',
            'location' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'url_location' => 'nullable|string|url',
            'verified_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'start_at.after' => 'Start date must be in the future',
            'end_at.after' => 'End date must be after start date',
            'seat_available.lte' => 'Available seats cannot exceed total seats',
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

    protected function prepareForValidation()
    {
        if ($this->has('seat') && !$this->has('seat_available')) {
            $this->merge([
                'seat_available' => $this->seat
            ]);
        }
    }
}
