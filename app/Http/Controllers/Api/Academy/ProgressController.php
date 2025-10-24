<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\ProgressStoreRequest;
use App\Http\Requests\Academy\ProgressUpdateRequest;
use App\Http\Resources\Academy\ProgressResource;
use App\Models\Progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Progress::class);

        $user = $request->user();

        $progress = Progress::query()
            ->with(['enrollment', 'user', 'course', 'lesson'])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->enrollment_id, fn($q) => $q->where('enrollment_id', $request->enrollment_id))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->lesson_id, fn($q) => $q->where('lesson_id', $request->lesson_id))
            ->when($request->status === 'completed', fn($q) => $q->completed())
            ->when($request->status === 'incomplete', fn($q) => $q->incomplete())
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ProgressResource::collection($progress);
    }

    public function store(ProgressStoreRequest $request)
    {
        $this->authorize('create', Progress::class);

        try {
            $validated = $request->validated();

            $user = $request->user();
            if ($user && $user->role === 'instructor') {
                $isInstructorOfCourse = \App\Models\Course::query()
                    ->whereKey($validated['course_id'])
                    ->whereHas('instructors', fn($q) => $q->where('users.id', $user->id))
                    ->exists();

                if (!$isInstructorOfCourse) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You are not allowed to create progress for this course',
                    ], 403);
                }
            }

            $progress = Progress::create($validated);
            $progress->load(['enrollment', 'user', 'course', 'lesson']);

            return response()->json([
                'status' => 'success',
                'message' => 'Progress created successfully',
                'data' => new ProgressResource($progress)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating progress: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create progress'
            ], 500);
        }
    }

    public function show(Progress $progress)
    {
        $this->authorize('view', $progress);

        $progress->load(['enrollment', 'user', 'course', 'lesson']);

        return new ProgressResource($progress);
    }

    public function update(ProgressUpdateRequest $request, Progress $progress)
    {
        $this->authorize('update', $progress);

        try {
            $progress->update($request->validated());
            $progress->load(['enrollment', 'user', 'course', 'lesson']);

            return response()->json([
                'status' => 'success',
                'message' => 'Progress updated successfully',
                'data' => new ProgressResource($progress)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating progress: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update progress'
            ], 500);
        }
    }

    public function destroy(Progress $progress)
    {
        $this->authorize('delete', $progress);

        try {
            $progress->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Progress deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting progress: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete progress'
            ], 500);
        }
    }

    public function complete(Progress $progress)
    {
        $this->authorize('complete', $progress);

        try {
            $progress->update(['is_completed' => true]);
            $progress->load(['enrollment', 'user', 'course', 'lesson']);

            return response()->json([
                'status' => 'success',
                'message' => 'Progress marked as completed',
                'data' => new ProgressResource($progress)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error completing progress: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete progress'
            ], 500);
        }
    }
}
