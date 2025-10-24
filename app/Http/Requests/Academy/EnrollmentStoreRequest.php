<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class EnrollmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        if ($user && $user->role === 'student') {
            return [
                'course_id' => 'required|exists:courses,id',
                'enrolled_at' => 'nullable|date',
                'quiz_attempts' => 'nullable|integer|min:0',
            ];
        }

        return [
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'enrolled_at' => 'nullable|date',
            'quiz_attempts' => 'nullable|integer|min:0',
        ];
    }
}
