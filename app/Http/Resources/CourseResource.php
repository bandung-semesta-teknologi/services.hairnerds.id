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
            'is_highlight' => $this->is_highlight,
            'status' => $this->status,
            'thumbnail' => $this->thumbnail,
            'verified_at' => $this->verified_at,
            'faqs' => $this->when($this->relationLoaded('faqs'), function () {
                return $this->faqs->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ];
                });
            }),
            'sections' => $this->when($this->relationLoaded('sections'), function () {
                return $this->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'sequence' => $section->sequence,
                        'title' => $section->title,
                        'objective' => $section->objective,
                        'lessons' => $section->relationLoaded('lessons')
                            ? $section->lessons->map(function ($lesson) {
                                return [
                                    'id' => $lesson->id,
                                    'sequence' => $lesson->sequence,
                                    'type' => $lesson->type,
                                    'title' => $lesson->title,
                                    'url' => $lesson->url,
                                    'summary' => $lesson->summary,
                                    'datetime' => $lesson->datetime,
                                ];
                            })
                            : null,
                    ];
                });
            }),
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
