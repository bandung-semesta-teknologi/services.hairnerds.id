<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizResultStoreRequest;
use App\Http\Requests\QuizResultUpdateRequest;
use App\Http\Resources\QuizResultResource;
use App\Models\QuizResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizResultController extends Controller
{
    public function index(Request $request)
    {
        $quizResults = QuizResult::query()
            ->with(['user', 'quiz', 'lesson'])
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->quiz_id, fn($q) => $q->where('quiz_id', $request->quiz_id))
            ->when($request->lesson_id, fn($q) => $q->where('lesson_id', $request->lesson_id))
            ->when($request->status === 'submitted', fn($q) => $q->submitted())
            ->when($request->status === 'in_progress', fn($q) => $q->inProgress())
            ->latest('started_at')
            ->paginate($request->per_page ?? 15);

        return QuizResultResource::collection($quizResults);
    }

    public function store(QuizResultStoreRequest $request)
    {
        try {
            $quizResult = QuizResult::create($request->validated());
            $quizResult->load(['user', 'quiz', 'lesson']);

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz result created successfully',
                'data' => new QuizResultResource($quizResult)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating quiz result: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create quiz result'
            ], 500);
        }
    }

    public function show(QuizResult $quizResult)
    {
        $quizResult->load(['user', 'quiz', 'lesson']);

        return new QuizResultResource($quizResult);
    }

    public function update(QuizResultUpdateRequest $request, QuizResult $quizResult)
    {
        try {
            $quizResult->update($request->validated());
            $quizResult->load(['user', 'quiz', 'lesson']);

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz result updated successfully',
                'data' => new QuizResultResource($quizResult)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating quiz result: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update quiz result'
            ], 500);
        }
    }

    public function destroy(QuizResult $quizResult)
    {
        try {
            $quizResult->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz result deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting quiz result: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete quiz result'
            ], 500);
        }
    }

    public function submit(QuizResult $quizResult)
    {
        try {
            $quizResult->update([
                'is_submitted' => true,
                'finished_at' => now()
            ]);
            $quizResult->load(['user', 'quiz', 'lesson']);

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz submitted successfully',
                'data' => new QuizResultResource($quizResult)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error submitting quiz: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit quiz'
            ], 500);
        }
    }
}
