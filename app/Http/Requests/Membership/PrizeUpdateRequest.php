<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrizeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'type' => ['sometimes', Rule::in(['physical', 'promo_code'])],
            'point_cost' => 'sometimes|required|integer|min:1',
            'total_stock' => 'sometimes|required|integer|min:1',
            'available_stock' => 'nullable|integer|min:0',
            'blocked_stock' => 'nullable|integer|min:0',
            'used_stock' => 'nullable|integer|min:0',
            'redemption_start_date' => 'sometimes|required|date',
            'redemption_end_date' => 'sometimes|required|date|after_or_equal:redemption_start_date',
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'banner_image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'redemption_end_date.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('total_stock')) {
                $availableStock = $this->available_stock ?? $this->route('prize')->available_stock ?? 0;
                $blockedStock = $this->blocked_stock ?? $this->route('prize')->blocked_stock ?? 0;
                $usedStock = $this->used_stock ?? $this->route('prize')->used_stock ?? 0;

                $total = $availableStock + $blockedStock + $usedStock;

                if ($total > $this->total_stock) {
                    $validator->errors()->add('total_stock', 'Sum of available, blocked, and used stock cannot exceed total stock');
                }
            }

            if ($this->has('available_stock') && !$this->has('total_stock')) {
                $prize = $this->route('prize');
                if ($prize && $this->available_stock > $prize->total_stock) {
                    $validator->errors()->add('available_stock', 'Available stock cannot exceed total stock');
                }
            }
        });
    }
}
