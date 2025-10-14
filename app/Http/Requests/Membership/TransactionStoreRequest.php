<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;

class TransactionStoreRequest extends FormRequest
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
            'card_number' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|string|in:fixed,percentage',
            'total' => 'required|numeric|min:0',
            'merchant_id' => 'required|numeric',
            'merchant_user_id' => 'required|string|max:50',
            'merchant_name' => 'required|string|max:100',
            'merchant_email' => 'required|email|max:100',
        ];
    }
}
