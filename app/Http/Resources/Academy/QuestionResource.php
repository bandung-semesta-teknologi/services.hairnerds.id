<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'quiz' => $this->when($this->relationLoaded('quiz'), function () {
                return [
                    'id' => $this->quiz->id,
                    'title' => $this->quiz->title,
                    'instruction' => $this->quiz->instruction,
                    'duration' => $this->quiz->duration?->format('H:i:s'),
                    'total_marks' => $this->quiz->total_marks,
                    'pass_marks' => $this->quiz->pass_marks,
                ];
            }),
            'type' => $this->type,
            'question' => $this->question,
            'score' => $this->score,
            'answer_banks' => $this->when($this->relationLoaded('answerBanks'), function () {
                return $this->answerBanks->map(function ($answerBank) {
                    return [
                        'id' => $answerBank->id,
                        'answer' => $answerBank->answer,
                        'is_true' => $answerBank->is_true,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
