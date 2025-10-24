<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class EnrollmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|required|exists:users,id',
            'course_id' => 'sometimes|required|exists:courses,id',
            'enrolled_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
            'quiz_attempts' => 'nullable|integer|min:0',
        ];
    }
}
