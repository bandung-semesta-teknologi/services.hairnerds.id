<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'sequence' => $this->sequence,
            'type' => $this->type,
            'title' => $this->title,
            'url' => $this->url,
            'summary' => $this->summary,
            'datetime' => $this->datetime,

            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'attachments_count' => $this->whenLoaded('attachments', fn() => $this->attachments->count(), 0),

            'quiz' => $this->when(
                $this->type === 'quiz' && $this->relationLoaded('quiz'),
                function () {
                    if (!$this->quiz) {
                        return null;
                    }

                    return [
                        'id' => $this->quiz->id,
                        'title' => $this->quiz->title,
                        'instruction' => $this->quiz->instruction,
                        'duration' => $this->quiz->duration?->format('H:i:s'),
                        'total_marks' => $this->quiz->total_marks,
                        'pass_marks' => $this->quiz->pass_marks,
                        'max_retakes' => $this->quiz->max_retakes,
                        'min_lesson_taken' => $this->quiz->min_lesson_taken,
                        'questions_count' => $this->quiz->relationLoaded('questions')
                            ? $this->quiz->questions->count()
                            : 0,
                        'questions' => $this->quiz->relationLoaded('questions')
                            ? $this->quiz->questions->map(function ($question) {
                                return [
                                    'id' => $question->id,
                                    'type' => $question->type,
                                    'question' => $question->question,
                                    'score' => $question->score,
                                    'answer_banks_count' => $question->relationLoaded('answerBanks')
                                        ? $question->answerBanks->count()
                                        : 0,
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
                            })
                            : null,
                    ];
                }
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
