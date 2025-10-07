<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'faqable_type' => $this->faqable_type,
            'faqable_id' => $this->faqable_id,
            'faqable' => $this->when($this->relationLoaded('faqable'), function () {
                $faqableType = class_basename($this->faqable_type);

                if ($faqableType === 'Course') {
                    return [
                        'id' => $this->faqable->id,
                        'title' => $this->faqable->title,
                        'slug' => $this->faqable->slug,
                        'type' => 'course',
                    ];
                }

                if ($faqableType === 'Bootcamp') {
                    return [
                        'id' => $this->faqable->id,
                        'title' => $this->faqable->title,
                        'type' => 'bootcamp',
                    ];
                }

                return null;
            }),
            'question' => $this->question,
            'answer' => $this->answer,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
