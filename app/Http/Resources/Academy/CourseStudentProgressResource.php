<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseStudentProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $completedLessons = (int) ($this->completed_lessons_count ?? 0);
        $totalLessons = (int) ($this->total_lessons ?? 0);
        $percentage = $totalLessons > 0 ? (int) floor(($completedLessons / $totalLessons) * 100) : 0;

        return [
            'student' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'progress' => [
                'percentage' => $percentage,
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
                'quiz_taken' => (int) ($this->quiz_taken ?? 0),
                'total_quizzes' => (int) ($this->total_quizzes ?? 0),
            ],
            'activity' => [
                'enrolled_at' => optional($this->enrolled_at)->toISOString(),
                'last_seen_at' => optional($this->last_seen_at)->toISOString(),
                'completed_at' => optional($this->finished_at)->toISOString(),
            ],
        ];
    }
}


