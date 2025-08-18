<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => 'required|exists:sections,id',
            'course_id' => 'required|exists:courses,id',
            'sequence' => 'required|integer|min:1',
            'type' => ['required', Rule::in(['youtube', 'document', 'text', 'audio', 'live', 'quiz'])],
            'title' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'datetime' => 'nullable|date',
        ];
    }
}
