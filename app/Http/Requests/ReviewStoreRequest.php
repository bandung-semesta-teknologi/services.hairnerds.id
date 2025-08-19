<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'user_id' => 'required|exists:users,id',
            'comments' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'is_visible' => 'nullable|boolean',
        ];
    }
}
