<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseWithFaqStoreRequest;
use App\Http\Requests\CourseWithFaqUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Faq;
use App\Services\CategoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CourseWithFaqController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function store(CourseWithFaqStoreRequest $request)
    {
        $this->authorize('create', Course::class);

        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $categories = $data['category_ids'] ?? [];
                $instructorIds = $data['instructor_ids'] ?? [];
                $faqs = $data['faqs'] ?? [];

                unset($data['category_ids'], $data['instructor_ids'], $data['faqs']);

                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
                }

                $course = Course::create($data);

                if (!empty($categories)) {
                    $categoryIds = $this->categoryService->resolveCategoryIds($categories);
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

                $course->load(['categories', 'instructors', 'faqs']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Course with FAQs created successfully',
                    'data' => new CourseResource($course)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating course with FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create course with FAQs'
            ], 500);
        }
    }

    public function update(CourseWithFaqUpdateRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        try {
            return DB::transaction(function () use ($request, $course) {
                $data = $request->validated();
                $categories = $data['category_ids'] ?? null;
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

                if (!empty($data)) {
                    $course->update($data);
                }

                if ($categories !== null) {
                    $categoryIds = $this->categoryService->resolveCategoryIds($categories);
                    $course->categories()->sync($categoryIds);
                }

                if ($instructorIds !== null) {
                    $course->instructors()->sync($instructorIds);
                }

                if ($faqs !== null) {
                    $existingFaqIds = $course->faqs()->pluck('id')->toArray();
                    $submittedFaqIds = [];

                    foreach ($faqs as $faqData) {
                        if (!empty($faqData['id'])) {
                            $faq = $course->faqs()->find($faqData['id']);
                            if ($faq) {
                                $faq->update([
                                    'question' => $faqData['question'],
                                    'answer' => $faqData['answer'],
                                ]);
                                $submittedFaqIds[] = $faq->id;
                            }
                        } else {
                            $newFaq = $course->faqs()->create([
                                'question' => $faqData['question'],
                                'answer' => $faqData['answer'],
                            ]);
                            $submittedFaqIds[] = $newFaq->id;
                        }
                    }

                    $faqsToDelete = array_diff($existingFaqIds, $submittedFaqIds);
                    if (!empty($faqsToDelete)) {
                        $course->faqs()->whereIn('id', $faqsToDelete)->delete();
                    }
                }

                $course->load(['categories', 'instructors', 'faqs']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Course with FAQs updated successfully',
                    'data' => new CourseResource($course)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating course with FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update course with FAQs'
            ], 500);
        }
    }
}
