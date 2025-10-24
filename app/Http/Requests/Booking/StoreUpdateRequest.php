<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => 'required|string|max:100|unique:stores,store_name,' . $this->store->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
