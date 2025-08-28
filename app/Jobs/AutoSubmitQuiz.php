<?php

namespace App\Jobs;

use App\Models\QuizResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoSubmitQuiz implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quizResultId;

    public function __construct($quizResultId)
    {
        $this->quizResultId = $quizResultId;
    }

    public function handle(): void
    {
        try {
            $quizResult = QuizResult::with(['quiz', 'user'])->find($this->quizResultId);

            if (!$quizResult) {
                Log::warning("QuizResult not found: {$this->quizResultId}");
                return;
            }

            if ($quizResult->is_submitted) {
                Log::info("Quiz already submitted: {$this->quizResultId}");
                return;
            }

            if (!$quizResult->isExpired()) {
                Log::info("Quiz not expired yet: {$this->quizResultId}");
                return;
            }

            $quizResult->autoSubmit();

            Log::info("Quiz auto-submitted successfully: {$this->quizResultId}");

        } catch (\Exception $e) {
            Log::error("Failed to auto-submit quiz {$this->quizResultId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("AutoSubmitQuiz job failed for quiz result {$this->quizResultId}: " . $exception->getMessage());
    }
}
