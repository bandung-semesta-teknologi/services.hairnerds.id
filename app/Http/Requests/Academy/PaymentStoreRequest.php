<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payable_type' => 'required|string|in:course,bootcamp',
            'payable_id' => 'required|integer|exists:courses,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $payableType = $this->input('payable_type');
            $payableId = $this->input('payable_id');

            if ($payableType === 'bootcamp') {
                if (!\App\Models\Bootcamp::where('id', $payableId)->exists()) {
                    $validator->errors()->add('payable_id', 'The selected bootcamp does not exist.');
                }
            }
        });
    }
}
