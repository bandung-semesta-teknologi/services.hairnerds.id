<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
                    'short_description' => $this->course->short_description,
                    'level' => $this->course->level,
                    'price' => $this->course->price,
                ];
            }),
            'sequence' => $this->sequence,
            'title' => $this->title,
            'objective' => $this->objective,
            'lessons' => $this->when($this->relationLoaded('lessons'), function () {
                return $this->lessons->map(function ($lesson) {
                    return [
                        'id' => $lesson->id,
                        'sequence' => $lesson->sequence,
                        'type' => $lesson->type,
                        'title' => $lesson->title,
                        'url' => $lesson->url,
                        'summary' => $lesson->summary,
                        'datetime' => $lesson->datetime,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
