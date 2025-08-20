<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin,instructor')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $courses = Course::query()
            ->with(['categories', 'faqs', 'sections', 'instructors', 'reviews'])
            ->when(!$this->isAdminOrInstructor($request), fn($q) => $q->published())
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->level, fn($q) => $q->where('level', $request->level))
            ->when($request->status && $this->isAdminOrInstructor($request), fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return CourseResource::collection($courses);
    }

    public function store(CourseStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? [];
            $instructorIds = $data['instructor_ids'] ?? [];
            unset($data['category_ids'], $data['instructor_ids']);

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            }

            $course = Course::create($data);

            if (!empty($categoryIds)) {
                $course->categories()->attach($categoryIds);
            }

            if (!empty($instructorIds)) {
                $course->instructors()->attach($instructorIds);
            }

            $course->load(['categories', 'instructors']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course created successfully',
                'data' => new CourseResource($course)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create course'
            ], 500);
        }
    }

    public function show(Request $request, Course $course)
    {
        if (!$this->isAdminOrInstructor($request) && $course->status !== 'published') {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);
        }

        $course->load(['categories', 'faqs', 'sections', 'instructors', 'reviews']);

        return new CourseResource($course);
    }

    public function update(CourseUpdateRequest $request, Course $course)
    {
        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? null;
            $instructorIds = $data['instructor_ids'] ?? null;
            unset($data['category_ids'], $data['instructor_ids']);

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            }

            $course->update($data);

            if ($categoryIds !== null) {
                $course->categories()->sync($categoryIds);
            }

            if ($instructorIds !== null) {
                $course->instructors()->sync($instructorIds);
            }

            $course->load(['categories', 'instructors']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course updated successfully',
                'data' => new CourseResource($course)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update course'
            ], 500);
        }
    }

    public function destroy(Course $course)
    {
        try {
            $course->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Course deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete course'
            ], 500);
        }
    }

    private function isAdminOrInstructor(Request $request): bool
    {
        $user = $request->user();
        return $user && in_array($user->role, ['admin', 'instructor']);
    }
}
