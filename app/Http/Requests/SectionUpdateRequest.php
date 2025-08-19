<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SectionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'sometimes|required|exists:courses,id',
            'sequence' => 'sometimes|required|integer|min:1',
            'title' => 'sometimes|required|string|max:255',
            'objective' => 'nullable|string|max:255',
        ];
    }
}
