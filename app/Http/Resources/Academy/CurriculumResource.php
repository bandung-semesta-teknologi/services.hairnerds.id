<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurriculumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => $this->when($this->relationLoaded('course'), function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                    'status' => $this->course->status,
                ];
            }),
            'sequence' => $this->sequence,
            'title' => $this->title,
            'objective' => $this->objective,
            'lessons' => $this->when($this->relationLoaded('lessons'), function () {
                return $this->lessons->map(function ($lesson) {
                    $lessonData = [
                        'id' => $lesson->id,
                        'section_id' => $lesson->section_id,
                        'course_id' => $lesson->course_id,
                        'sequence' => $lesson->sequence,
                        'type' => $lesson->type,
                        'title' => $lesson->title,
                        'url' => $lesson->url,
                        'summary' => $lesson->summary,
                        'datetime' => $lesson->datetime,
                    ];

                    if (in_array($lesson->type, ['document', 'audio'])) {
                        $lessonData['attachments'] = $lesson->relationLoaded('attachments')
                            ? $lesson->attachments->map(function ($attachment) {
                                return [
                                    'id' => $attachment->id,
                                    'lesson_id' => $attachment->lesson_id,
                                    'type' => $attachment->type,
                                    'title' => $attachment->title,
                                    'url' => $attachment->url,
                                    'full_url' => $attachment->full_url,
                                ];
                            })
                            : [];
                    }

                    if ($lesson->type === 'quiz') {
                        $lessonData['quiz'] = null;

                        if ($lesson->relationLoaded('quiz') && $lesson->quiz) {
                            $quiz = $lesson->quiz;

                            $lessonData['quiz'] = [
                                'id' => $quiz->id,
                                'section_id' => $quiz->section_id,
                                'lesson_id' => $quiz->lesson_id,
                                'course_id' => $quiz->course_id,
                                'title' => $quiz->title,
                                'instruction' => $quiz->instruction,
                                'duration' => $quiz->duration?->format('H:i:s'),
                                'total_marks' => $quiz->total_marks,
                                'pass_marks' => $quiz->pass_marks,
                                'max_retakes' => $quiz->max_retakes,
                                'min_lesson_taken' => $quiz->min_lesson_taken,
                            ];

                            if ($quiz->relationLoaded('questions')) {
                                $lessonData['quiz']['questions'] = $quiz->questions->map(function ($question) {
                                    $questionData = [
                                        'id' => $question->id,
                                        'quiz_id' => $question->quiz_id,
                                        'type' => $question->type,
                                        'question' => $question->question,
                                        'score' => $question->score,
                                    ];

                                    if ($question->relationLoaded('answerBanks')) {
                                        $questionData['answers'] = $question->answerBanks->map(function ($answer) {
                                            return [
                                                'id' => $answer->id,
                                                'question_id' => $answer->question_id,
                                                'answer' => $answer->answer,
                                                'is_true' => $answer->is_true,
                                            ];
                                        });
                                    }

                                    return $questionData;
                                });
                            }
                        }
                    }

                    return $lessonData;
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
