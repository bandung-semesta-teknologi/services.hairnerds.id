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
            'is_free' => $this->isFree(),
            'is_paid' => $this->isPaid(),
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
                        'user_name' => $review->user?->name,
                        'email' => $review->user?->email,
                        'user_avatar' => $review->user?->userProfile?->avatar,
                        'comments' => $review->comments,
                        'rating' => $review->rating,
                        'is_visible' => $review->is_visible,
                        'created_at' => $review->created_at,
                    ];
                });
            }),
            'reviews_summary' => $this->when($this->relationLoaded('reviews') || isset($this->reviews_count), function () {
                $totalReviews = $this->reviews_count ?? $this->reviews->count();
                $averageRating = $this->reviews_avg_rating ?? ($this->reviews->count() > 0 ? round($this->reviews->avg('rating'), 1) : null);

                $ratingDistribution = null;
                if ($this->relationLoaded('reviews') && $this->reviews->count() > 0) {
                    $ratingDistribution = [
                        '5_stars' => $this->reviews->where('rating', 5)->count(),
                        '4_stars' => $this->reviews->where('rating', 4)->count(),
                        '3_stars' => $this->reviews->where('rating', 3)->count(),
                        '2_stars' => $this->reviews->where('rating', 2)->count(),
                        '1_star' => $this->reviews->where('rating', 1)->count(),
                    ];
                }

                return [
                    'total_reviews' => $totalReviews,
                    'average_rating' => $averageRating,
                    'rating_distribution' => $ratingDistribution,
                ];
            }),
            'sections_count' => $this->whenLoaded('sections', fn() => $this->sections->count(), 0),
            'lessons_count' => $this->whenLoaded('sections', function () {
                return $this->sections->sum(function ($section) {
                    return $section->relationLoaded('lessons') ? $section->lessons->count() : 0;
                });
            }, 0),
            'faqs_count' => $this->whenLoaded('faqs', fn() => $this->faqs->count(), 0),
            'reviews_count' => $this->reviews_count ?? ($this->whenLoaded('reviews', fn() => $this->reviews->count(), 0)),
            'students_count' => $this->whenLoaded('enrollments', fn() => $this->enrollments->count(), 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
