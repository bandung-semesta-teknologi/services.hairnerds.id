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
            'title' => $this->title,
            'slug' => $this->slug,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'seat' => $this->seat,
            'seat_available' => $this->seat_available,
            'seat_blocked' => $this->seat_blocked,
            'seat_taken' => $this->seat - $this->seat_available - $this->seat_blocked,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'thumbnail' => $this->thumbnail,
            'instructors' => UserResource::collection($this->whenLoaded('instructors')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'faqs' => $this->when($this->relationLoaded('faqs'), function () {
                return $this->faqs->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ];
                });
            }),
            'status' => $this->status,
            'price' => $this->price,
            'is_free' => $this->isFree(),
            'is_paid' => $this->isPaid(),
            'location' => $this->location,
            'contact_person' => $this->contact_person,
            'url_location' => $this->url_location,
            'verified_at' => $this->verified_at,
            'is_available' => $this->isAvailable(),
            'duration_days' => $this->start_at->diffInDays($this->end_at) + 1,
            'enrolled_students_count' => $this->when(
                $this->relationLoaded('payments'),
                function () {
                    return $this->payments()
                        ->where('status', 'paid')
                        ->count();
                },
                0
            ),
            'total_revenue' => $this->when(
                $this->relationLoaded('payments'),
                function () {
                    return $this->payments()
                        ->where('status', 'paid')
                        ->sum('total');
                },
                0
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
