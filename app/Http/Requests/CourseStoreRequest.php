<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string|max:255',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'instructor_ids' => 'nullable|array',
            'instructor_ids.*' => 'exists:users,id',
            'level' => ['required', Rule::in(['beginner', 'adv', 'interm'])],
            'lang' => 'required|string|max:50',
            'price' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|max:2048',
            'verified_at' => 'nullable|date',
        ];
    }
}
