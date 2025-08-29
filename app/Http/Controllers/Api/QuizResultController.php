<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizResultStoreRequest;
use App\Http\Requests\QuizResultUpdateRequest;
use App\Http\Resources\QuizResultResource;
use App\Jobs\AutoSubmitQuiz;
use App\Models\QuizResult;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuizResultController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', QuizResult::class);

        $user = $request->user();

        $quizResults = QuizResult::query()
            ->with(['user', 'quiz.course', 'lesson'])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('quiz.course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
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
        $this->authorize('create', QuizResult::class);

        try {
            $data = $request->validated();
            $user = $request->user();

            if ($user->role === 'student') {
                $data['user_id'] = $user->id;

                $quiz = Quiz::with('course')->findOrFail($data['quiz_id']);

                if ($quiz->course->status !== 'published') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot take quiz from unpublished course'
                    ], 422);
                }

                $isEnrolled = $user->enrollments()
                    ->where('course_id', $quiz->course_id)
                    ->exists();

                if (!$isEnrolled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Must be enrolled in course to take quiz'
                    ], 422);
                }

                $existingActiveQuiz = QuizResult::where('user_id', $user->id)
                    ->where('quiz_id', $data['quiz_id'])
                    ->where('is_submitted', false)
                    ->exists();

                if ($existingActiveQuiz) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You already have an active quiz attempt'
                    ], 422);
                }
            }

            if ($user->role === 'instructor') {
                $quiz = Quiz::with('course')->findOrFail($data['quiz_id']);

                if (!$quiz->course->instructors->contains($user)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot create quiz result for course you do not teach'
                    ], 403);
                }
            }

            $quizResult = QuizResult::create($data);
            $quizResult->load(['user', 'quiz.course', 'lesson']);

            if ($quizResult->quiz->duration && $user->role === 'student') {
                $duration = $quizResult->quiz->duration;
                if (is_string($duration)) {
                    $durationParts = explode(':', $duration);
                } else {
                    $durationParts = explode(':', $duration->format('H:i:s'));
                }

                $hours = (int) $durationParts[0];
                $minutes = (int) $durationParts[1];
                $seconds = (int) $durationParts[2];

                $delaySeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                $bufferSeconds = 1 * 60;
                $totalDelaySeconds = $delaySeconds + $bufferSeconds;

                AutoSubmitQuiz::dispatch($quizResult->id)->delay($totalDelaySeconds);
            }

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
        $this->authorize('view', $quizResult);

        $quizResult->load(['user', 'quiz.course', 'lesson']);

        return new QuizResultResource($quizResult);
    }

    public function update(QuizResultUpdateRequest $request, QuizResult $quizResult)
    {
        $this->authorize('update', $quizResult);

        try {
            $data = $request->validated();
            $user = $request->user();

            if ($quizResult->is_submitted) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quiz already submitted'
                ], 422);
            }

            if ($user->role === 'student' && $quizResult->isExpired()) {
                $quizResult->autoSubmit();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Quiz time has expired and has been automatically submitted',
                    'data' => new QuizResultResource($quizResult->fresh(['user', 'quiz.course', 'lesson']))
                ], 422);
            }

            if (isset($data['is_submitted']) && $data['is_submitted']) {
                $enrollment = $quizResult->user->enrollments()
                    ->where('course_id', $quizResult->quiz->course_id)
                    ->first();

                if ($enrollment) {
                    $enrollment->increment('quiz_attempts');
                }

                if (!isset($data['finished_at'])) {
                    $data['finished_at'] = now();
                }
            }

            $quizResult->update($data);
            $quizResult->load(['user', 'quiz.course', 'lesson']);

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
        $this->authorize('delete', $quizResult);

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
        $this->authorize('submit', $quizResult);

        try {
            if ($quizResult->is_submitted) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quiz result already submitted'
                ], 422);
            }

            $quizResult->update([
                'is_submitted' => true,
                'finished_at' => now()
            ]);

            $enrollment = $quizResult->user->enrollments()
                ->where('course_id', $quizResult->quiz->course_id)
                ->first();

            if ($enrollment) {
                $enrollment->increment('quiz_attempts');
            }

            $quizResult->load(['user', 'quiz.course', 'lesson']);

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
