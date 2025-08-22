<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BootcampResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'instructor' => new UserResource($this->whenLoaded('user')),
            'title' => $this->title,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'seat' => $this->seat,
            'seat_available' => $this->seat_available,
            'seat_blocked' => $this->seat_blocked,
            'seat_taken' => $this->seat - $this->seat_available - $this->seat_blocked,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'status' => $this->status,
            'price' => $this->price,
            'location' => $this->location,
            'contact_person' => $this->contact_person,
            'url_location' => $this->url_location,
            'verified_at' => $this->verified_at,
            'is_available' => $this->isAvailable(),
            'duration_days' => $this->start_at->diffInDays($this->end_at) + 1,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
