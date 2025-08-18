<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonStoreRequest;
use App\Http\Requests\LessonUpdateRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $lessons = Lesson::query()
            ->with(['section', 'course'])
            ->when($request->section_id, fn($q) => $q->where('section_id', $request->section_id))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->ordered()
            ->paginate($request->per_page ?? 15);

        return LessonResource::collection($lessons);
    }

    public function store(LessonStoreRequest $request)
    {
        $lesson = Lesson::create($request->validated());
        $lesson->load(['section', 'course']);

        return new LessonResource($lesson);
    }

    public function show(Lesson $lesson)
    {
        $lesson->load(['section', 'course']);

        return new LessonResource($lesson);
    }

    public function update(LessonUpdateRequest $request, Lesson $lesson)
    {
        $lesson->update($request->validated());
        $lesson->load(['section', 'course']);

        return new LessonResource($lesson);
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return response()->json([
            'message' => 'Lesson deleted successfully'
        ]);
    }
}
