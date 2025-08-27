<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonStoreRequest;
use App\Http\Requests\LessonUpdateRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lesson::class);

        $user = $request->user();

        $lessons = Lesson::query()
            ->with(['section', 'course'])
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
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->ordered()
            ->paginate($request->per_page ?? 15);

        return LessonResource::collection($lessons);
    }

    public function store(LessonStoreRequest $request)
    {
        $this->authorize('create', Lesson::class);

        try {
            $lesson = Lesson::create($request->validated());
            $lesson->load(['section', 'course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Lesson created successfully',
                'data' => new LessonResource($lesson)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating lesson: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create lesson'
            ], 500);
        }
    }

    public function show(Lesson $lesson)
    {
        $this->authorize('view', $lesson);

        $lesson->load(['section', 'course']);

        return new LessonResource($lesson);
    }

    public function update(LessonUpdateRequest $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson);

        try {
            $lesson->update($request->validated());
            $lesson->load(['section', 'course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Lesson updated successfully',
                'data' => new LessonResource($lesson)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating lesson: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update lesson'
            ], 500);
        }
    }

    public function destroy(Lesson $lesson)
    {
        $this->authorize('delete', $lesson);

        try {
            $lesson->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Lesson deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting lesson: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete lesson'
            ], 500);
        }
    }
}
