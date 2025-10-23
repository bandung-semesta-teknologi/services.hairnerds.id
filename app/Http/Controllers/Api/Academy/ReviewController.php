<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\ReviewStoreRequest;
use App\Http\Requests\Academy\ReviewUpdateRequest;
use App\Http\Resources\Academy\ReviewResource;
use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', Review::class);

        $reviews = Review::query()
            ->with(['course', 'user'])
            ->when(!$user || $user->role === 'student', function($q) {
                return $q->whereHas('course', fn($q) => $q->where('status', 'published'))
                         ->where('is_visible', true);
            })
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->rating, fn($q) => $q->where('rating', $request->rating))
            ->when($request->is_visible !== null, fn($q) => $q->where('is_visible', $request->boolean('is_visible')))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ReviewResource::collection($reviews);
    }

    public function store(ReviewStoreRequest $request)
    {
        $this->authorize('create', Review::class);

        try {
            $data = $request->validated();

            if ($request->user()->role === 'student') {
                $course = Course::findOrFail($data['course_id']);

                $isEnrolled = $request->user()->enrollments()
                    ->where('course_id', $course->id)
                    ->exists();

                if (!$isEnrolled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only review courses you are enrolled in'
                    ], 422);
                }

                $existingReview = Review::where('user_id', $request->user()->id)
                    ->where('course_id', $course->id)
                    ->exists();

                if ($existingReview) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You have already reviewed this course'
                    ], 422);
                }
            }

            $review = Review::create($data);
            $review->load(['course', 'user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Review created successfully',
                'data' => new ReviewResource($review)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating review: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create review'
            ], 500);
        }
    }

    public function show(Request $request, Review $review)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('view', $review);

        $review->load(['course', 'user']);

        return new ReviewResource($review);
    }

    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        try {
            $review->update($request->validated());
            $review->load(['course', 'user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Review updated successfully',
                'data' => new ReviewResource($review)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating review: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update review'
            ], 500);
        }
    }

    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        try {
            $review->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Review deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting review: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete review'
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
