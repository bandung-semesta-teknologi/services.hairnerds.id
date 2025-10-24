<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CatalogCategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_category' => 'required|string|max:25|unique:service_categories,name_category',
            'gender' => 'nullable|integer|in:0,1,2,3',
            'status' => 'nullable|integer|in:0,1',
            'sequence' => 'required|integer|min:0',
            'image' => 'nullable|string|max:50',
            'id_store' => 'required|exists:stores,id',
            'is_recommendation' => 'nullable|integer|in:0,1',
            'is_distance_matter' => 'nullable|boolean',
        ];
    }
}
