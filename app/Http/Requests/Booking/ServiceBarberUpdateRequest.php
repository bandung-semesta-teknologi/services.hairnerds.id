<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ServiceBarberUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_barber' => 'required|exists:barbers,id',
            'id_service' => 'required|exists:services,id',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'status' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'id_barber.required' => 'Barber wajib dipilih.',
            'id_service.required' => 'Service wajib dipilih.',
            'price.required' => 'Harga wajib diisi.',
            'duration.required' => 'Durasi wajib diisi.',
        ];
    }
}
