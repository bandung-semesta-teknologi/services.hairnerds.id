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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
