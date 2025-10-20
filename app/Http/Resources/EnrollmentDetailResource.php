<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progressMap = $this->progress->keyBy('lesson_id');
        $totalLessons = $this->course->sections->sum(fn($section) => $section->lessons->count());
        $completedLessons = $this->progress->where('is_completed', true)->count();
        $completionPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;

        return [
            'id' => $this->id,
            'enrolled_at' => $this->enrolled_at,
            'finished_at' => $this->finished_at,
            'is_finished' => $this->finished_at !== null,
            'quiz_attempts' => $this->quiz_attempts,
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'slug' => $this->course->slug,
                'short_description' => $this->course->short_description,
                'description' => $this->course->description,
                'level' => $this->course->level,
                'price' => $this->course->price,
                'thumbnail' => $this->course->thumbnail,
                'language' => $this->course->language,
                'instructors' => $this->course->instructors->map(function ($instructor) {
                    return [
                        'id' => $instructor->id,
                        'name' => $instructor->name,
                        'avatar' => $instructor->avatar,
                    ];
                }),
                'categories' => $this->course->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ];
                }),
                'faqs' => $this->course->faqs->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ];
                }),
            ],
            'curriculum' => $this->course->sections->sortBy('sequence')->map(function ($section) use ($progressMap) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'sequence' => $section->sequence,
                    'lessons' => $section->lessons->sortBy('sequence')->map(function ($lesson) use ($progressMap) {
                        $progress = $progressMap->get($lesson->id);

                        $lessonData = [
                            'id' => $lesson->id,
                            'sequence' => $lesson->sequence,
                            'type' => $lesson->type,
                            'title' => $lesson->title,
                            'is_completed' => $progress ? $progress->is_completed : false,
                            'score' => $progress ? $progress->score : null,
                        ];

                        return $lessonData;
                    }),
                ];
            }),
            'progress_summary' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'remaining_lessons' => $totalLessons - $completedLessons,
                'completion_percentage' => $completionPercentage,
            ],
            'continue_learning' => [
                'section' => $this->getNextSection(),
                'lesson' => $this->getNextLesson(),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getNextSection()
    {
        $progressMap = $this->progress->keyBy('lesson_id');

        foreach ($this->course->sections->sortBy('sequence') as $section) {
            $sectionCompleted = true;
            foreach ($section->lessons as $lesson) {
                $progress = $progressMap->get($lesson->id);
                if (!$progress || !$progress->is_completed) {
                    $sectionCompleted = false;
                    break;
                }
            }

            if (!$sectionCompleted) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'sequence' => $section->sequence,
                ];
            }
        }

        return null;
    }

    private function getNextLesson()
    {
        $progressMap = $this->progress->keyBy('lesson_id');

        foreach ($this->course->sections->sortBy('sequence') as $section) {
            foreach ($section->lessons->sortBy('sequence') as $lesson) {
                $progress = $progressMap->get($lesson->id);
                if (!$progress || !$progress->is_completed) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'type' => $lesson->type,
                        'sequence' => $lesson->sequence,
                        'section_id' => $section->id,
                        'section_title' => $section->title,
                    ];
                }
            }
        }

        return null;
    }
}
