<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class EnrollmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'course' => $this->when($this->relationLoaded('course'), function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                    'short_description' => $this->course->short_description,
                    'thumbnail' => $this->course->thumbnail,
                    'level' => $this->course->level,
                    'price' => $this->course->price,
                    'instructors' => $this->when($this->course->relationLoaded('instructors'), function () {
                        return UserResource::collection($this->course->instructors);
                    }),
                    'categories' => $this->when($this->course->relationLoaded('categories'), function () {
                        return CategoryResource::collection($this->course->categories);
                    }),
                    'sections' => $this->when($this->course->relationLoaded('sections'), function () {
                        return $this->course->sections->map(function ($section) {
                            return [
                                'id' => $section->id,
                                'title' => $section->title,
                                'sequence' => $section->sequence,
                                'lessons' => $section->relationLoaded('lessons') ? $section->lessons->map(function ($lesson) {
                                    return [
                                        'id' => $lesson->id,
                                        'title' => $lesson->title,
                                        'type' => $lesson->type,
                                        'sequence' => $lesson->sequence,
                                        'url' => $lesson->url,
                                        'summary' => $lesson->summary,
                                        'duration' => $lesson->duration,
                                    ];
                                }) : [],
                            ];
                        });
                    }),
                ];
            }),
            'enrolled_at' => $this->enrolled_at,
            'finished_at' => $this->finished_at,
            'quiz_attempts' => $this->quiz_attempts,
            'is_finished' => $this->finished_at !== null,
            'completion_percentage' => $this->completion_percentage ?? 0,
            'total_lessons' => $this->total_lessons ?? 0,
            'completed_lessons' => $this->completed_lessons ?? 0,
            'total_quizzes' => $this->total_quizzes ?? 0,
            'completed_quizzes' => $this->completed_quizzes ?? 0,
            'last_activity_at' => $this->last_activity_at,
            'progress' => $this->when($this->relationLoaded('progress'), function () {
                return $this->progress->map(function ($progress) {
                    return [
                        'id' => $progress->id,
                        'lesson' => $progress->relationLoaded('lesson') ? [
                            'id' => $progress->lesson->id,
                            'sequence' => $progress->lesson->sequence,
                            'type' => $progress->lesson->type,
                            'title' => $progress->lesson->title,
                            'url' => $progress->lesson->url,
                            'summary' => $progress->lesson->summary,
                        ] : null,
                        'is_completed' => $progress->is_completed,
                        'score' => $progress->score,
                        'created_at' => $progress->created_at,
                        'updated_at' => $progress->updated_at,
                    ];
                });
            }),
            'is_reviewed' => $this->when($this->relationLoaded('course'), function() {
                return $this->course->relationLoaded('reviews')
                    ? $this->course->reviews->isNotEmpty()
                    : false;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
