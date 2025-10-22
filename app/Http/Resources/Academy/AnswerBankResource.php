<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerBankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'question' => $this->when($this->relationLoaded('question'), function () {
                return [
                    'id' => $this->question->id,
                    'type' => $this->question->type,
                    'question' => $this->question->question,
                    'score' => $this->question->score,
                ];
            }),
            'answer' => $this->answer,
            'is_true' => $this->is_true,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
