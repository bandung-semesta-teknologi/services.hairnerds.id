<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerBankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'question' => new QuestionResource($this->whenLoaded('question')),
            'answer' => $this->answer,
            'is_true' => $this->is_true,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
