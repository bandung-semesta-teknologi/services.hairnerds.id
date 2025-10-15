<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrizeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => ['required', Rule::in(['physical', 'promo_code'])],
            'point_cost' => 'required|integer|min:1',
            'total_stock' => 'required|integer|min:1',
            'available_stock' => 'nullable|integer|min:0|lte:total_stock',
            'blocked_stock' => 'nullable|integer|min:0',
            'used_stock' => 'nullable|integer|min:0',
            'redemption_start_date' => 'required|date',
            'redemption_end_date' => 'required|date|after_or_equal:redemption_start_date',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'banner_image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'redemption_end_date.after_or_equal' => 'End date must be after or equal to start date',
            'available_stock.lte' => 'Available stock cannot exceed total stock',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('total_stock') && $this->has('available_stock') && $this->has('blocked_stock') && $this->has('used_stock')) {
                $total = ($this->available_stock ?? 0) + ($this->blocked_stock ?? 0) + ($this->used_stock ?? 0);
                if ($total > $this->total_stock) {
                    $validator->errors()->add('total_stock', 'Sum of available, blocked, and used stock cannot exceed total stock');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        if ($this->has('total_stock') && !$this->has('available_stock')) {
            $this->merge([
                'available_stock' => $this->total_stock
            ]);
        }
    }
}
