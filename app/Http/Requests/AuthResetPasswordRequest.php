<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('token') && $this->has('email') && !$this->has('email') !== null) {
            $this->merge([
                'email' => $this->query('email', $this->input('email'))
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }
}
