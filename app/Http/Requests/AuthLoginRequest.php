<?php

namespace App\Http\Requests;

use App\Enums\CredentialType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthLoginRequest extends FormRequest
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
        $type = $this->input('type');
        $rules = [
            'type' => ['required', Rule::enum(CredentialType::class)],
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        if ($type === CredentialType::Email->value) {
            $rules['identifier'][] = 'email';
        }

        return $rules;
    }
}
