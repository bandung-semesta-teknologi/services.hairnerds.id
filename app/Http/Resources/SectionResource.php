<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'sequence' => $this->sequence,
            'title' => $this->title,
            'objective' => $this->objective,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
