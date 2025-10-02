<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'lesson' => $this->when($this->relationLoaded('lesson'), function () {
                return [
                    'id' => $this->lesson->id,
                    'sequence' => $this->lesson->sequence,
                    'type' => $this->lesson->type,
                    'title' => $this->lesson->title,
                ];
            }),
            'type' => $this->type,
            'title' => $this->title,
            'url' => $this->url,
            'full_url' => $this->full_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
