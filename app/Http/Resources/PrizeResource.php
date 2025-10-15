<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrizeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'point_cost' => $this->point_cost,
            'total_stock' => $this->total_stock,
            'available_stock' => $this->available_stock,
            'blocked_stock' => $this->blocked_stock,
            'used_stock' => $this->used_stock,
            'redemption_start_date' => $this->redemption_start_date,
            'redemption_end_date' => $this->redemption_end_date,
            'status' => $this->status,
            'banner_image' => $this->banner_image,
            'created_by' => $this->created_by,
            'is_available' => $this->isAvailable(),
            'is_active' => $this->isActive(),
            'is_redemption_active' => $this->isRedemptionActive(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
