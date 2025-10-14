<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoursesBootcampsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseData = [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'thumbnail' => $this->thumbnail,
            'price' => $this->price,
            'is_free' => $this->type === 'course' ? $this->isFree() : $this->isFree(),
            'is_paid' => $this->type === 'course' ? $this->isPaid() : $this->isPaid(),
            'instructors' => UserResource::collection($this->whenLoaded('instructors')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->type === 'course') {
            return array_merge($baseData, [
                'level' => $this->level,
                'is_highlight' => $this->is_highlight,
                'sections_count' => $this->whenLoaded('sections', fn() => $this->sections->count(), 0),
                'lessons_count' => $this->whenLoaded('sections', function () {
                    return $this->sections->sum(function ($section) {
                        return $section->relationLoaded('lessons') ? $section->lessons->count() : 0;
                    });
                }, 0),
                'reviews_count' => $this->reviews_count ?? ($this->whenLoaded('reviews', fn() => $this->reviews->count(), 0)),
                'students_count' => $this->enrollments_count ?? 0,
                'average_rating' => $this->reviews_avg_rating ? (float) $this->reviews_avg_rating : null,
            ]);
        }

        if ($this->type === 'bootcamp') {
            return array_merge($baseData, [
                'start_at' => $this->start_at,
                'end_at' => $this->end_at,
                'seat' => $this->seat,
                'seat_available' => $this->seat_available,
                'seat_taken' => $this->seat - $this->seat_available - $this->seat_blocked,
                'location' => $this->location,
                'contact_person' => $this->contact_person,
                'url_location' => $this->url_location,
                'is_available' => $this->isAvailable(),
                'duration_days' => $this->start_at->diffInDays($this->end_at) + 1,
            ]);
        }

        return $baseData;
    }
}
