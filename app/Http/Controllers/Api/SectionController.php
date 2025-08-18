<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SectionStoreRequest;
use App\Http\Requests\SectionUpdateRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $sections = Section::query()
            ->with(['course', 'lessons'])
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->ordered()
            ->paginate($request->per_page ?? 15);

        return SectionResource::collection($sections);
    }

    public function store(SectionStoreRequest $request)
    {
        $section = Section::create($request->validated());
        $section->load('course');

        return new SectionResource($section);
    }

    public function show(Section $section)
    {
        $section->load(['course', 'lessons']);

        return new SectionResource($section);
    }

    public function update(SectionUpdateRequest $request, Section $section)
    {
        $section->update($request->validated());
        $section->load('course');

        return new SectionResource($section);
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully'
        ]);
    }
}
