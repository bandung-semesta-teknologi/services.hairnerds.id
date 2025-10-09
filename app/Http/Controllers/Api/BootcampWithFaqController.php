<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BootcampWithFaqStoreRequest;
use App\Http\Requests\BootcampWithFaqUpdateRequest;
use App\Http\Resources\BootcampResource;
use App\Models\Bootcamp;
use App\Services\CategoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BootcampWithFaqController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function store(BootcampWithFaqStoreRequest $request)
    {
        $this->authorize('create', Bootcamp::class);

        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $categories = $data['category_ids'] ?? [];
                $faqs = $data['faqs'] ?? [];

                unset($data['category_ids'], $data['faqs']);

                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
                }

                $user = $request->user();
                if ($user->role === 'admin' && isset($data['instructor_id'])) {
                    $data['user_id'] = $data['instructor_id'];
                } else {
                    $data['user_id'] = $user->id;
                }
                unset($data['instructor_id']);

                $data['seat_available'] = $data['seat_available'] ?? $data['seat'];

                $bootcamp = Bootcamp::create($data);

                if (!empty($categories)) {
                    $categoryIds = $this->categoryService->resolveCategoryIds($categories);
                    $bootcamp->categories()->attach($categoryIds);
                }

                foreach ($faqs as $faq) {
                    $bootcamp->faqs()->create([
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }

                $bootcamp->load(['user', 'categories', 'faqs']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Bootcamp with FAQs created successfully',
                    'data' => new BootcampResource($bootcamp)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating bootcamp with FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bootcamp with FAQs'
            ], 500);
        }
    }

    public function update(BootcampWithFaqUpdateRequest $request, Bootcamp $bootcamp)
    {
        $this->authorize('update', $bootcamp);

        try {
            return DB::transaction(function () use ($request, $bootcamp) {
                $data = $request->validated();
                $categories = $data['category_ids'] ?? null;
                $faqs = $data['faqs'] ?? null;

                unset($data['category_ids'], $data['faqs']);

                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
                }

                $user = $request->user();
                if ($user->role === 'admin' && isset($data['instructor_id'])) {
                    $data['user_id'] = $data['instructor_id'];
                } elseif (isset($data['instructor_id'])) {
                    unset($data['instructor_id']);
                }
                unset($data['instructor_id']);

                if ($bootcamp->status === 'rejected' && !isset($data['status'])) {
                    $data['status'] = 'draft';
                    $data['verified_at'] = null;
                }

                if (!empty($data)) {
                    $bootcamp->update($data);
                }

                if ($categories !== null) {
                    $categoryIds = $this->categoryService->resolveCategoryIds($categories);
                    $bootcamp->categories()->sync($categoryIds);
                }

                if ($faqs !== null) {
                    $existingFaqIds = $bootcamp->faqs()->pluck('id')->toArray();
                    $submittedFaqIds = [];

                    foreach ($faqs as $faqData) {
                        if (!empty($faqData['id'])) {
                            $faq = $bootcamp->faqs()->find($faqData['id']);
                            if ($faq) {
                                $faq->update([
                                    'question' => $faqData['question'],
                                    'answer' => $faqData['answer'],
                                ]);
                                $submittedFaqIds[] = $faq->id;
                            }
                        } else {
                            $newFaq = $bootcamp->faqs()->create([
                                'question' => $faqData['question'],
                                'answer' => $faqData['answer'],
                            ]);
                            $submittedFaqIds[] = $newFaq->id;
                        }
                    }

                    $faqsToDelete = array_diff($existingFaqIds, $submittedFaqIds);
                    if (!empty($faqsToDelete)) {
                        $bootcamp->faqs()->whereIn('id', $faqsToDelete)->delete();
                    }
                }

                $bootcamp->load(['user', 'categories', 'faqs']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Bootcamp with FAQs updated successfully',
                    'data' => new BootcampResource($bootcamp)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating bootcamp with FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bootcamp with FAQs'
            ], 500);
        }
    }
}
