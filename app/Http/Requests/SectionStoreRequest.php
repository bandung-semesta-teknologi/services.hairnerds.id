<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'sequence' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'objective' => 'nullable|string|max:255',
        ];
    }
}
