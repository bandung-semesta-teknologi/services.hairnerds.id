<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqStoreRequest;
use App\Http\Requests\FaqUpdateRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Models\Course;
use App\Models\Bootcamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', Faq::class);

        $requestedType = $request->input('type');
        $mappedType = null;
        if (is_string($requestedType)) {
            $normalized = strtolower(trim($requestedType));
            if ($normalized === 'course') {
                $mappedType = Course::class;
            } elseif ($normalized === 'bootcamp') {
                $mappedType = Bootcamp::class;
            }
        }

        $requestedId = $request->input('id_referens');

        $query = Faq::query()
            ->with('faqable')
            ->when(!$user || $user->role === 'student', function($q) {
                return $q->where(function($query) {
                    $query->whereHasMorph('faqable', [Course::class], function($q) {
                        $q->where('status', 'published');
                    })->orWhereHasMorph('faqable', [Bootcamp::class], function($q) {
                        $q->where('status', 'publish');
                    });
                });
            })
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->where(function($query) use ($user) {
                    $query->whereHasMorph('faqable', [Course::class], function($q) use ($user) {
                        $q->whereHas('instructors', fn($q) => $q->where('users.id', $user->id));
                    })->orWhereHasMorph('faqable', [Bootcamp::class], function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                });
            })
            ->when($request->faqable_type ?? $mappedType, function($q) use ($request, $mappedType) {
                $type = $request->faqable_type ?? $mappedType;
                return $q->where('faqable_type', $type);
            })
            ->when($request->faqable_id ?? $requestedId, function($q) use ($request, $requestedId) {
                $id = $request->faqable_id ?? $requestedId;
                return $q->where('faqable_id', $id);
            });

        if ($request->has('paginate') && $request->paginate !== 'false') {
            $faqs = $query->paginate($request->per_page ?? 15);
        } else {
            $faqs = $query->get();
        }

        return FaqResource::collection($faqs);
    }

    public function store(FaqStoreRequest $request)
    {
        $this->authorize('create', Faq::class);

        try {
            $faqableType = $request->validated()['faqable_type'];
            $faqableId = $request->validated()['faqable_id'];
            $faqs = $request->validated()['faqs'];

            $createdFaqs = DB::transaction(function () use ($faqableType, $faqableId, $faqs) {
                $faqsToInsert = collect($faqs)->map(function ($faq) use ($faqableType, $faqableId) {
                    return [
                        'faqable_type' => $faqableType,
                        'faqable_id' => $faqableId,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                Faq::insert($faqsToInsert);

                return Faq::with('faqable')
                    ->where('faqable_type', $faqableType)
                    ->where('faqable_id', $faqableId)
                    ->latest()
                    ->limit(count($faqsToInsert))
                    ->get();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'FAQs created successfully',
                'data' => FaqResource::collection($createdFaqs)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating FAQs: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create FAQs'
            ], 500);
        }
    }

    public function show(Request $request, Faq $faq)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('view', $faq);

        $faq->load('faqable');

        return new FaqResource($faq);
    }

    public function update(FaqUpdateRequest $request, Faq $faq)
    {
        $this->authorize('update', $faq);

        try {
            $faq->update($request->validated());
            $faq->load('faqable');

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ updated successfully',
                'data' => new FaqResource($faq)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating FAQ: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update FAQ'
            ], 500);
        }
    }

    public function destroy(Faq $faq)
    {
        $this->authorize('delete', $faq);

        try {
            $faq->delete();

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
