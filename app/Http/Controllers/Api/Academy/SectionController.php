<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\SectionStoreRequest;
use App\Http\Requests\Academy\SectionUpdateRequest;
use App\Http\Requests\Academy\SectionUpdateSequenceRequest;
use App\Http\Resources\Academy\SectionResource;
use App\Http\Resources\Academy\SectionSimpleResource;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', Section::class);

        $sections = Section::query()
            ->with(['course', 'lessons'])
            ->when(!$user || $user->role === 'student', function($q) {
                return $q->whereHas('course', fn($q) => $q->where('status', 'published'));
            })
            ->when($user && $user->role === 'admin', fn($q) => $q)
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->sort_by, function($q) use ($request) {
                $sortBy = $request->sort_by;
                $sortOrder = $request->order_by ?? 'asc';

                if (in_array($sortBy, ['sequence', 'title', 'created_at', 'updated_at'])) {
                    return $q->orderBy($sortBy, $sortOrder);
                }

                return $q;
            }, function($q) {
                return $q->ordered();
            })
            ->paginate($request->per_page ?? 15);

        return SectionResource::collection($sections);
    }

    public function store(SectionStoreRequest $request)
    {
        $this->authorize('create', Section::class);

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

    public function show(Request $request, Section $section)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('view', $section);

        $section->load(['course', 'lessons']);

        return new SectionResource($section);
    }

    public function update(SectionUpdateRequest $request, Section $section)
    {
        $this->authorize('update', $section);

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

    public function updateSequence(SectionUpdateSequenceRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $sectionsData = $request->validated()['sections'];
                $sectionIds = collect($sectionsData)->pluck('id')->toArray();

                $sections = Section::whereIn('id', $sectionIds)->get();

                if ($sections->isEmpty()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No sections found'
                    ], 404);
                }

                $firstSection = $sections->first();
                $this->authorize('update', $firstSection);

                foreach ($sectionsData as $sectionData) {
                    Section::where('id', $sectionData['id'])
                        ->update(['sequence' => $sectionData['sequence']]);
                }

                $updatedSections = Section::whereIn('id', $sectionIds)
                    ->orderBy('sequence', 'asc')
                    ->get();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Section sequence updated successfully',
                    'data' => SectionSimpleResource::collection($updatedSections)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating section sequence: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update section sequence',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy(Section $section)
    {
        $this->authorize('delete', $section);

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

    private function resolveOptionalUser(Request $request)
    {
        if ($user = $request->user()) {
            return $user;
        }

        if ($token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            return $accessToken?->tokenable;
        }

        return null;
    }
}
