<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_category' => $this->name_category,
            'gender' => $this->gender,
            'status' => $this->status,
            'sequence' => $this->sequence,
            'image' => $this->image,
            'id_store' => $this->id_store,
            'is_recommendation' => (bool) $this->is_recommendation,
            'is_distance_matter' => (bool) $this->is_distance_matter,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
        ];
    }
}
