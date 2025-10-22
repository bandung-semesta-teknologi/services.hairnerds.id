<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'quiz_id' => $this->quiz_id,
            'quiz' => $this->when($this->relationLoaded('quiz'), function () {
                return [
                    'id' => $this->quiz->id,
                    'title' => $this->quiz->title,
                    'instruction' => $this->quiz->instruction,
                    'duration' => $this->quiz->duration?->format('H:i:s'),
                    'total_marks' => $this->quiz->total_marks,
                    'pass_marks' => $this->quiz->pass_marks,
                    'max_retakes' => $this->quiz->max_retakes,
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
