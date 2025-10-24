<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'picture' => $this->picture,
            'website' => $this->website,
            'id_owner' => $this->id_owner,
            'social_facebook' => $this->social_facebook,
            'social_instagram' => $this->social_instagram,
            'social_twitter' => $this->social_twitter,
            'is_active' => (bool) $this->is_active,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'delivery_charge' => $this->delivery_charge,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
        ];
    }
}
