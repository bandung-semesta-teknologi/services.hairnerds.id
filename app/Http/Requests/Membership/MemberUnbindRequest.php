<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;

class MemberUnbindRequest extends FormRequest
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
            'serial_number' => 'required|string|max:100',
            'card_number' => 'required|string|max:50',
            'used_by' => 'required|string',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string',
            'name' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
        ];
    }
}
