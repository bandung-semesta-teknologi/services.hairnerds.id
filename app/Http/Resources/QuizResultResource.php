<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'quiz_id' => $this->quiz_id,
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'lesson_id' => $this->lesson_id,
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'answered' => $this->answered,
            'correct_answers' => $this->correct_answers,
            'total_obtained_marks' => $this->total_obtained_marks,
            'is_submitted' => $this->is_submitted,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'duration_minutes' => $this->started_at && $this->finished_at ?
                $this->started_at->diffInMinutes($this->finished_at) : null,
            'pass_status' => $this->when($this->is_submitted && $this->quiz && $this->quiz->pass_marks, function () {
                return $this->total_obtained_marks >= $this->quiz->pass_marks ? 'passed' : 'failed';
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
