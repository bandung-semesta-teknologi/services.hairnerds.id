<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'what_will_learn' => $this->what_will_learn,
            'requirements' => $this->requirements,
            'category_id' => $this->category_id,
            'category' => new CourseCategoryResource($this->whenLoaded('category')),
            'level' => $this->level,
            'language' => $this->language,
            'enable_drip_content' => $this->enable_drip_content,
            'price' => $this->price,
            'thumbnail' => $this->thumbnail,
            'status' => $this->status,
            'faqs' => CourseFaqResource::collection($this->whenLoaded('faqs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
