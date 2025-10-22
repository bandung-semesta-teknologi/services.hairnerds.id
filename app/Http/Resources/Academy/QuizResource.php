<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'section' => $this->when($this->relationLoaded('section'), function () {
                return [
                    'id' => $this->section->id,
                    'sequence' => $this->section->sequence,
                    'title' => $this->section->title,
                    'objective' => $this->section->objective,
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
                    'datetime' => $this->lesson->datetime,
                ];
            }),
            'course_id' => $this->course_id,
            'course' => $this->when($this->relationLoaded('course'), function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                    'short_description' => $this->course->short_description,
                    'level' => $this->course->level,
                    'price' => $this->course->price,
                ];
            }),
            'title' => $this->title,
            'instruction' => $this->instruction,
            'duration' => $this->duration?->format('H:i:s'),
            'total_marks' => $this->total_marks,
            'pass_marks' => $this->pass_marks,
            'max_retakes' => $this->max_retakes,
            'min_lesson_taken' => $this->min_lesson_taken,
            'questions' => $this->when($this->relationLoaded('questions'), function () {
                return $this->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'type' => $question->type,
                        'question' => $question->question,
                        'score' => $question->score,
                        'answer_banks' => $question->relationLoaded('answerBanks')
                            ? $question->answerBanks->map(function ($answerBank) {
                                return [
                                    'id' => $answerBank->id,
                                    'answer' => $answerBank->answer,
                                    'is_true' => $answerBank->is_true,
                                ];
                            })
                            : null,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
