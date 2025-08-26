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
        $this->authorize('viewAny', Quiz::class);

        $user = $request->user();

        $quizzes = Quiz::query()
            ->with(['section', 'lesson', 'course', 'questions'])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->whereHas('course', function($q) {
                    $q->where('status', 'published');
                })->whereHas('course.enrollments', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
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
        $this->authorize('create', Quiz::class);

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
        $this->authorize('view', $quiz);

        $quiz->load(['section', 'lesson', 'course', 'questions']);

        return new QuizResource($quiz);
    }

    public function update(QuizUpdateRequest $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

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
        $this->authorize('delete', $quiz);

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
