<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BarberStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:100|unique:barbers,email',
            'name' => 'required|string|max:100',
            'id_store' => 'required|exists:stores,id',
            'color' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
