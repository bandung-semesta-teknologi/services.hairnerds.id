<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BootcampWithFaqStoreRequest;
use App\Http\Requests\BootcampWithFaqUpdateRequest;
use App\Http\Resources\BootcampResource;
use App\Models\Bootcamp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BootcampWithFaqController extends Controller
{
    public function store(BootcampWithFaqStoreRequest $request)
    {
        $this->authorize('create', Bootcamp::class);

        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $categoryIds = $data['category_ids'] ?? [];
                $instructorIds = $data['instructor_ids'] ?? [];
                $faqs = $data['faqs'] ?? [];

                unset($data['category_ids'], $data['instructor_ids'], $data['faqs']);

                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
                }

                $data['seat_available'] = $data['seat_available'] ?? $data['seat'];

                $bootcamp = Bootcamp::create($data);

                if (!empty($categoryIds)) {
                    $bootcamp->categories()->attach($categoryIds);
                }

                if (!empty($instructorIds)) {
                    $bootcamp->instructors()->attach($instructorIds);
                }

                foreach ($faqs as $faq) {
                    $bootcamp->faqs()->create([
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }

                $bootcamp->load(['instructors', 'categories', 'faqs']);

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
                $categoryIds = $data['category_ids'] ?? null;
                $instructorIds = $data['instructor_ids'] ?? null;
                $faqs = $data['faqs'] ?? null;

                unset($data['category_ids'], $data['instructor_ids'], $data['faqs']);

                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
                }

                if ($bootcamp->status === 'rejected' && !isset($data['status'])) {
                    $data['status'] = 'draft';
                    $data['verified_at'] = null;
                }

                if (!empty($data)) {
                    $bootcamp->update($data);
                }

                if ($categoryIds !== null) {
                    $bootcamp->categories()->sync($categoryIds);
                }

                if ($instructorIds !== null) {
                    $bootcamp->instructors()->sync($instructorIds);
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

                $bootcamp->load(['instructors', 'categories', 'faqs']);

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
