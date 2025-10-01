<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonStoreRequest;
use App\Http\Requests\LessonUpdateRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lesson::class);

        $user = $request->user();

        $lessons = Lesson::query()
            ->with(['section', 'course', 'attachments'])
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

        $lessons->getCollection()->transform(function ($lesson) {
            if ($lesson->type === 'quiz') {
                $lesson->load(['quiz.questions.answerBanks']);
            }
            return $lesson;
        });

        return LessonResource::collection($lessons);
    }

    public function store(LessonStoreRequest $request)
    {
        $this->authorize('create', Lesson::class);

        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();

                $attachmentTypes = $data['attachment_types'] ?? [];
                $attachmentTitles = $data['attachment_titles'] ?? [];
                $attachmentFiles = $data['attachment_files'] ?? [];
                $attachmentUrls = $data['attachment_urls'] ?? [];

                unset($data['attachment_types'], $data['attachment_titles'], $data['attachment_files'], $data['attachment_urls']);

                $lesson = Lesson::create($data);

                if (!empty($attachmentTypes)) {
                    foreach ($attachmentTypes as $index => $type) {
                        $attachmentData = [
                            'lesson_id' => $lesson->id,
                            'type' => $type,
                            'title' => $attachmentTitles[$index] ?? 'Untitled',
                        ];

                        if (isset($attachmentFiles[$index]) && $attachmentFiles[$index]) {
                            $file = $attachmentFiles[$index];
                            $path = $file->store('lessons/attachments', 'public');
                            $attachmentData['url'] = $path;
                        } elseif (isset($attachmentUrls[$index]) && $attachmentUrls[$index]) {
                            $attachmentData['url'] = $attachmentUrls[$index];
                        } else {
                            $attachmentData['url'] = '';
                        }

                        $lesson->attachments()->create($attachmentData);
                    }
                }

                $lesson->load(['section', 'course', 'attachments']);

                if ($lesson->type === 'quiz') {
                    $lesson->load(['quiz.questions.answerBanks']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Lesson created successfully',
                    'data' => new LessonResource($lesson)
                ], 201);
            });
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

        $lesson->load(['section', 'course', 'attachments']);

        if ($lesson->type === 'quiz') {
            $lesson->load(['quiz.questions.answerBanks']);
        }

        return new LessonResource($lesson);
    }

    public function update(LessonUpdateRequest $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson);

        try {
            return DB::transaction(function () use ($request, $lesson) {
                $data = array_filter([
                    'section_id' => $request->input('section_id'),
                    'course_id' => $request->input('course_id'),
                    'sequence' => $request->input('sequence'),
                    'type' => $request->input('type'),
                    'title' => $request->input('title'),
                    'url' => $request->input('url'),
                    'summary' => $request->input('summary'),
                    'datetime' => $request->input('datetime'),
                ], function($value) {
                    return $value !== null;
                });

                $attachmentTypes = $request->input('attachment_types');
                $attachmentTitles = $request->input('attachment_titles');
                $attachmentFiles = $request->file('attachment_files');
                $attachmentUrls = $request->input('attachment_urls');

                if (!empty($data)) {
                    $lesson->update($data);
                }

                if ($attachmentTypes !== null && !empty($attachmentTypes)) {
                    foreach ($attachmentTypes as $index => $type) {
                        $attachmentData = [
                            'lesson_id' => $lesson->id,
                            'type' => $type,
                            'title' => $attachmentTitles[$index] ?? 'Untitled',
                        ];

                        if (isset($attachmentFiles[$index]) && $attachmentFiles[$index]) {
                            $file = $attachmentFiles[$index];
                            $path = $file->store('lessons/attachments', 'public');
                            $attachmentData['url'] = $path;
                        } elseif (isset($attachmentUrls[$index]) && $attachmentUrls[$index]) {
                            $attachmentData['url'] = $attachmentUrls[$index];
                        } else {
                            $attachmentData['url'] = '';
                        }

                        $lesson->attachments()->create($attachmentData);
                    }
                }

                $lesson->refresh();
                $lesson->load(['section', 'course', 'attachments']);

                if ($lesson->type === 'quiz') {
                    $lesson->load(['quiz.questions.answerBanks']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Lesson updated successfully',
                    'data' => new LessonResource($lesson)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error updating lesson: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update lesson',
                'debug' => config('app.debug') ? $e->getMessage() : null
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
