<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::query()
            ->with(['course', 'user'])
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
        try {
            $review = Review::create($request->validated());
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

    public function show(Review $review)
    {
        $review->load(['course', 'user']);

        return new ReviewResource($review);
    }

    public function update(ReviewUpdateRequest $request, Review $review)
    {
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
}
