<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gender' => $this->gender,
            'name_service' => $this->name_service,
            'service_subtitle' => $this->service_subtitle,
            'id_category' => $this->id_category,
            'description' => $this->description,
            'youtube_code' => $this->youtube_code,
            'price_type' => $this->price_type,
            'price_description' => $this->price_description,
            'allow_visible' => (bool) $this->allow_visible,
            'session_duration' => $this->session_duration,
            'buffer_time' => $this->buffer_time,
            'image' => $this->image,
            'id_store' => $this->id_store,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
        ];
    }
}
