<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'section' => new SectionResource($this->whenLoaded('section')),
            'lesson_id' => $this->lesson_id,
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'title' => $this->title,
            'instruction' => $this->instruction,
            'duration' => $this->duration?->format('H:i:s'),
            'total_marks' => $this->total_marks,
            'pass_marks' => $this->pass_marks,
            'max_retakes' => $this->max_retakes,
            'min_lesson_taken' => $this->min_lesson_taken,
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
