<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

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
        $review = Review::create($request->validated());
        $review->load(['course', 'user']);

        return new ReviewResource($review);
    }

    public function show(Review $review)
    {
        $review->load(['course', 'user']);

        return new ReviewResource($review);
    }

    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $review->update($request->validated());
        $review->load(['course', 'user']);

        return new ReviewResource($review);
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
