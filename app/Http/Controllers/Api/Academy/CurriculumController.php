<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\CurriculumStoreRequest;
use App\Http\Requests\Academy\CurriculumUpdateRequest;
use App\Http\Resources\Academy\CurriculumResource;
use App\Models\Section;
use App\Services\CurriculumService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurriculumController extends Controller
{
    protected $curriculumService;

    public function __construct(CurriculumService $curriculumService)
    {
        $this->curriculumService = $curriculumService;
    }

    public function store(CurriculumStoreRequest $request)
    {
        $this->authorize('create', Section::class);

        try {
            return DB::transaction(function () use ($request) {
                $section = $this->curriculumService->createCurriculum($request->validated());

                $section->load([
                    'course',
                    'lessons.attachments',
                    'lessons.quiz.questions.answerBanks'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Curriculum created successfully',
                    'data' => new CurriculumResource($section)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating curriculum: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create curriculum',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(CurriculumUpdateRequest $request, Section $section)
    {
        $this->authorize('update', $section);

        try {
            return DB::transaction(function () use ($request, $section) {
                $section = $this->curriculumService->updateCurriculumLessons(
                    $section,
                    $request->validated()['lessons']
                );

                $section->load([
                    'course',
                    'lessons.attachments',
                    'lessons.quiz.questions.answerBanks'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Curriculum updated successfully',
                    'data' => new CurriculumResource($section)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating curriculum: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update curriculum',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function updateViaPost(CurriculumUpdateRequest $request, Section $section)
    {
        $this->authorize('update', $section);

        try {
            return DB::transaction(function () use ($request, $section) {
                $section = $this->curriculumService->updateCurriculumLessons(
                    $section,
                    $request->validated()['lessons']
                );

                $section->load([
                    'course',
                    'lessons.attachments',
                    'lessons.quiz.questions.answerBanks'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Curriculum updated successfully',
                    'data' => new CurriculumResource($section)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating curriculum via POST: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update curriculum',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
