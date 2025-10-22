<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class QuizStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => 'required|exists:sections,id',
            'lesson_id' => 'required|exists:lessons,id',
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'instruction' => 'nullable|string',
            'duration' => 'nullable|date_format:H:i:s',
            'total_marks' => 'nullable|integer|min:0',
            'pass_marks' => 'nullable|integer|min:0',
            'max_retakes' => 'nullable|integer|min:0',
            'min_lesson_taken' => 'nullable|integer|min:0',
        ];
    }
}
