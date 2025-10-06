<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseStudentProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string'],
            'sort_by' => ['sometimes', 'in:name,progress,enrolled_at,completed_at'],
            'sort_order' => ['sometimes', 'in:asc,desc'],
        ];
    }
}


