<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'type' => $this->type,
            'question' => $this->question,
            'score' => $this->score,
            'answer_banks' => AnswerBankResource::collection($this->whenLoaded('answerBanks')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
