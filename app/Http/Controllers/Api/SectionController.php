<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SectionStoreRequest;
use App\Http\Requests\SectionUpdateRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $section = Section::create($request->validated());
            $section->load('course');

            return response()->json([
                'status' => 'success',
                'message' => 'Section created successfully',
                'data' => new SectionResource($section)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating section: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create section'
            ], 500);
        }
    }

    public function show(Section $section)
    {
        $section->load(['course', 'lessons']);

        return new SectionResource($section);
    }

    public function update(SectionUpdateRequest $request, Section $section)
    {
        try {
            $section->update($request->validated());
            $section->load('course');

            return response()->json([
                'status' => 'success',
                'message' => 'Section updated successfully',
                'data' => new SectionResource($section)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating section: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update section'
            ], 500);
        }
    }

    public function destroy(Section $section)
    {
        try {
            $section->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Section deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting section: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete section'
            ], 500);
        }
    }
}
