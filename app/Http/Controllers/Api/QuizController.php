<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizStoreRequest;
use App\Http\Requests\QuizUpdateRequest;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query()
            ->with(['section', 'lesson', 'course', 'questions'])
            ->when($request->section_id, fn($q) => $q->where('section_id', $request->section_id))
            ->when($request->lesson_id, fn($q) => $q->where('lesson_id', $request->lesson_id))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return QuizResource::collection($quizzes);
    }

    public function store(QuizStoreRequest $request)
    {
        try {
            $quiz = Quiz::create($request->validated());
            $quiz->load(['section', 'lesson', 'course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz created successfully',
                'data' => new QuizResource($quiz)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating quiz: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create quiz'
            ], 500);
        }
    }

    public function show(Quiz $quiz)
    {
        $quiz->load(['section', 'lesson', 'course', 'questions']);

        return new QuizResource($quiz);
    }

    public function update(QuizUpdateRequest $request, Quiz $quiz)
    {
        try {
            $quiz->update($request->validated());
            $quiz->load(['section', 'lesson', 'course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz updated successfully',
                'data' => new QuizResource($quiz)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating quiz: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update quiz'
            ], 500);
        }
    }

    public function destroy(Quiz $quiz)
    {
        try {
            $quiz->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting quiz: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete quiz'
            ], 500);
        }
    }
}
