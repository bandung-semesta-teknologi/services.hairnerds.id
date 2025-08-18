<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::query()
            ->with(['categories', 'faqs', 'sections', 'instructors', 'reviews'])
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->level, fn($q) => $q->where('level', $request->level))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return CourseResource::collection($courses);
    }

    public function store(CourseStoreRequest $request)
    {
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

        return new CourseResource($course);
    }

    public function show(Course $course)
    {
        $course->load(['categories', 'faqs', 'sections', 'instructors', 'reviews']);

        return new CourseResource($course);
    }

    public function update(CourseUpdateRequest $request, Course $course)
    {
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

        return new CourseResource($course);
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }
}
