<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'instructors' => UserResource::collection($this->whenLoaded('instructors')),
            'level' => $this->level,
            'lang' => $this->lang,
            'price' => $this->price,
            'thumbnail' => $this->thumbnail,
            'verified_at' => $this->verified_at,
            'faqs' => CourseFaqResource::collection($this->whenLoaded('faqs')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'reviews' => $this->when($this->relationLoaded('reviews'), function () {
                return $this->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'user_id' => $review->user_id,
                        'comments' => $review->comments,
                        'rating' => $review->rating,
                        'is_visible' => $review->is_visible,
                        'created_at' => $review->created_at,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
