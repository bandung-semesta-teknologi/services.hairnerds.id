<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class AuthUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('user_credentials', 'identifier')
                    ->where('type', 'phone')
                    ->ignore(
                        $this->user()
                            ->userCredentials()
                            ->where('type', 'phone')
                            ->first()
                            ?->id
                    ),
            ],
            'address' => 'nullable|string|max:1000',
            'avatar' => [
                'nullable',
                'image',
                File::image()
                    ->max(8 * 1024)
                    ->dimensions(Rule::dimensions()->maxWidth(1000)->maxHeight(500))
            ],
            'date_of_birth' => 'nullable|date',
            'short_biography' => 'nullable|string|max:500',
            'biography' => 'nullable|string|max:5000',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',

            'socials' => 'nullable|array',
            'socials.*.id' => 'nullable|exists:socials,id',
            'socials.*.type' => ['required_with:socials', Rule::in(['instagram', 'facebook', 'twitter', 'linkedin', 'youtube', 'tiktok'])],
            'socials.*.url' => 'required_with:socials|url|max:500',
        ];
    }
}
