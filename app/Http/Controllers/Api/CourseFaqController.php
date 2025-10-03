<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseFaqStoreRequest;
use App\Http\Requests\CourseFaqUpdateRequest;
use App\Http\Resources\CourseFaqResource;
use App\Models\CourseFaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseFaqController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', CourseFaq::class);

        $faqs = CourseFaq::query()
            ->with('course')
            ->when(!$user || $user->role === 'student', function($q) {
                return $q->whereHas('course', fn($q) => $q->where('status', 'published'));
            })
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->get();

        return CourseFaqResource::collection($faqs);
    }

    public function store(CourseFaqStoreRequest $request)
    {
        $this->authorize('create', CourseFaq::class);

        try {
            $courseId = $request->validated()['course_id'];
            $faqs = $request->validated()['faqs'];

            $createdFaqs = DB::transaction(function () use ($courseId, $faqs) {
                $faqsToInsert = collect($faqs)->map(function ($faq) use ($courseId) {
                    return [
                        'course_id' => $courseId,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                CourseFaq::insert($faqsToInsert);

                return CourseFaq::with('course')
                    ->where('course_id', $courseId)
                    ->latest()
                    ->limit(count($faqsToInsert))
                    ->get();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'FAQs created successfully',
                'data' => CourseFaqResource::collection($createdFaqs)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create FAQs'
            ], 500);
        }
    }

    public function show(Request $request, CourseFaq $coursesFaq)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('view', $coursesFaq);

        $coursesFaq->load('course');

        return new CourseFaqResource($coursesFaq);
    }

    public function update(CourseFaqUpdateRequest $request, CourseFaq $coursesFaq)
    {
        $this->authorize('update', $coursesFaq);

        try {
            $coursesFaq->update($request->validated());
            $coursesFaq->load('course');

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ updated successfully',
                'data' => new CourseFaqResource($coursesFaq)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating FAQ: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update FAQ'
            ], 500);
        }
    }

    public function destroy(CourseFaq $coursesFaq)
    {
        $this->authorize('delete', $coursesFaq);

        try {
            $coursesFaq->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting FAQ: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete FAQ'
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
