<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class CourseFaqUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'sometimes|required|exists:courses,id',
            'question' => 'sometimes|required|string|max:500',
            'answer' => 'sometimes|required|string|max:2000',
        ];
    }
}
