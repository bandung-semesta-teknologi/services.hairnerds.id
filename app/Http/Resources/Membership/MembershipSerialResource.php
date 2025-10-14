<?php

namespace App\Http\Resources\Membership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipSerialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'serial_number' => $this->serial_number,
            'card_number' => $this->card_number,
            'type' => $this->type,
            'is_used' => $this->is_used,
            'used_by' => $this->used_by,
            'used_at' => $this->used_at,
        ];
    }
}
