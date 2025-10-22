<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class ProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => 'sometimes|required|exists:enrollments,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'course_id' => 'sometimes|required|exists:courses,id',
            'lesson_id' => 'sometimes|required|exists:lessons,id',
            'is_completed' => 'nullable|boolean',
            'score' => 'nullable|integer|min:0',
        ];
    }
}
