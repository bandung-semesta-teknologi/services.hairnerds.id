<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseUpdateRequest extends FormRequest
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
            'level' => ['sometimes', 'required', Rule::in(['beginner', 'adv', 'interm'])],
            'lang' => 'sometimes|required|string|max:50',
            'price' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|max:2048',
            'verified_at' => 'nullable|date',
        ];
    }
}
