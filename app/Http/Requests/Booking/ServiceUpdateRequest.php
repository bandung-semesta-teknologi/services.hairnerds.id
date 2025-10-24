<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gender' => 'nullable|integer|in:0,1,2,3',
            'name_service' => 'required|string|max:255|unique:services,name_service,' . $this->service->id,
            'service_subtitle' => 'nullable|string|max:50',
            'id_category' => 'required|exists:service_categories,id',
            'description' => 'nullable|string',
            'youtube_code' => 'nullable|string|max:255',
            'price_type' => 'nullable|integer|in:1,2,3',
            'price_description' => 'nullable|string|max:30',
            'allow_visible' => 'nullable|boolean',
            'session_duration' => 'nullable|date_format:H:i:s',
            'buffer_time' => 'nullable|date_format:H:i:s',
            'image' => 'nullable|string|max:50',
            'id_store' => 'required|exists:stores,id',
        ];
    }
}
