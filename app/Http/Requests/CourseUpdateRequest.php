<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

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
            'what_will_learn' => 'nullable|string',
            'requirements' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:course_categories,id',
            'level' => ['sometimes', 'required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'language' => 'nullable|string|max:50',
            'enable_drip_content' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => [
                'nullable',
                'image',
                File::image()->max(2 * 1024)
            ],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
