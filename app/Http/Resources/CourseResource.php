<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'level' => $this->level,
            'lang' => $this->lang,
            'price' => $this->price,
            'thumbnail' => $this->thumbnail,
            'verified_at' => $this->verified_at,
            'faqs' => CourseFaqResource::collection($this->whenLoaded('faqs')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
