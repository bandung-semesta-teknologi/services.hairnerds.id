<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'enrollment' => $this->when($this->relationLoaded('enrollment'), function () {
                return [
                    'id' => $this->enrollment->id,
                    'enrolled_at' => $this->enrollment->enrolled_at,
                    'finished_at' => $this->enrollment->finished_at,
                    'quiz_attempts' => $this->enrollment->quiz_attempts,
                    'is_finished' => $this->enrollment->finished_at !== null,
                ];
            }),
            'user_id' => $this->user_id,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'course_id' => $this->course_id,
            'course' => $this->when($this->relationLoaded('course'), function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                    'level' => $this->course->level,
                ];
            }),
            'lesson_id' => $this->lesson_id,
            'lesson' => $this->when($this->relationLoaded('lesson'), function () {
                return [
                    'id' => $this->lesson->id,
                    'sequence' => $this->lesson->sequence,
                    'type' => $this->lesson->type,
                    'title' => $this->lesson->title,
                    'url' => $this->lesson->url,
                    'summary' => $this->lesson->summary,
                ];
            }),
            'is_completed' => $this->is_completed,
            'score' => $this->score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
