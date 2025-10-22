<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class ReviewUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'sometimes|required|exists:courses,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'comments' => 'sometimes|required|string',
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'is_visible' => 'nullable|boolean',
        ];
    }
}
