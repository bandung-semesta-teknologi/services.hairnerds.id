<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class AuthUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'avatar' => [
                'nullable',
                'image',
                File::image()
                    ->max(8 * 1024)
                    ->dimensions(Rule::dimensions()->maxWidth(1000)->maxHeight(500))
            ],
            'date_of_birth' => 'nullable|date'
        ];
    }
}
