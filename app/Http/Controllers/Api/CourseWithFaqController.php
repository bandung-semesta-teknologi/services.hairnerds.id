<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseWithFaqStoreRequest;
use App\Http\Requests\CourseWithFaqUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseWithFaqController extends Controller
{
    public function store(CourseWithFaqStoreRequest $request)
    {
        $this->authorize('create', Course::class);

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? [];
            $instructorIds = $data['instructor_ids'] ?? [];
            $faqs = $data['faqs'] ?? [];

            unset($data['category_ids'], $data['instructor_ids'], $data['faqs']);

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

            if (!empty($faqs)) {
                foreach ($faqs as $faq) {
                    $course->faqs()->create([
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }

            DB::commit();

            $course->load(['categories', 'instructors', 'faqs']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course with FAQs created successfully',
                'data' => new CourseResource($course)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating course with FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create course with FAQs',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(CourseWithFaqUpdateRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? null;
            $instructorIds = $data['instructor_ids'] ?? null;
            $faqs = $data['faqs'] ?? null;

            unset($data['category_ids'], $data['instructor_ids'], $data['faqs']);

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            }

            if ($course->status === 'rejected' && !isset($data['status'])) {
                $data['status'] = 'draft';
                $data['verified_at'] = null;
            }

            $course->update($data);

            if ($categoryIds !== null) {
                $course->categories()->sync($categoryIds);
            }

            if ($instructorIds !== null) {
                $course->instructors()->sync($instructorIds);
            }

            if ($faqs !== null) {
                $requestFaqIds = collect($faqs)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $course->faqs()
                    ->whereNotIn('id', $requestFaqIds)
                    ->delete();

                foreach ($faqs as $faqData) {
                    if (isset($faqData['id'])) {
                        $faq = $course->faqs()->find($faqData['id']);
                        if ($faq) {
                            $faq->update([
                                'question' => $faqData['question'],
                                'answer' => $faqData['answer'],
                            ]);
                        }
                    } else {
                        $course->faqs()->create([
                            'question' => $faqData['question'],
                            'answer' => $faqData['answer'],
                        ]);
                    }
                }
            }

            DB::commit();

            $course->load(['categories', 'instructors', 'faqs']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course with FAQs updated successfully',
                'data' => new CourseResource($course)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating course with FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update course with FAQs',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
