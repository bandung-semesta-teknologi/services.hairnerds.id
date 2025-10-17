<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
                $lessons = $this->course->relationLoaded('lessons') ? $this->course->lessons : collect();
                $quizLessons = $lessons->filter(fn($lesson) => $lesson->type === 'quiz');
                $nonQuizLessons = $lessons->filter(fn($lesson) => $lesson->type !== 'quiz');
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                    'short_description' => $this->course->short_description,
                    'thumbnail' => $this->course->thumbnail,
                    'level' => $this->course->level,
                    'price' => $this->course->price,
                    'instructors' => UserResource::collection($this->whenLoaded('course.instructors')),
                    'categories' => CategoryResource::collection($this->whenLoaded('course.categories')),
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
