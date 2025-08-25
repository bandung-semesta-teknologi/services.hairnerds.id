<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'enrolled_at' => $this->enrolled_at,
            'finished_at' => $this->finished_at,
            'quiz_attempts' => $this->quiz_attempts,
            'is_finished' => $this->finished_at !== null,
            'progress' => ProgressResource::collection($this->whenLoaded('progress')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
