<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Requests\CourseVerificationRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', Course::class);

        $courses = Course::query()
            ->with([
                'categories',
                'faqs',
                'sections.lessons',
                'instructors',
                'reviews.user.userProfile',
                'enrollments'
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when(!$user || $user->role === 'student', fn($q) => $q->published())
            ->when($user && $user->role === 'admin', fn($q) => $q)
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->level, fn($q) => $q->where('level', $request->level))
            ->when($request->is_highlight !== null, fn($q) => $q->where('is_highlight', $request->boolean('is_highlight')))
            ->when($request->status && $user && $user->role === 'admin', fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->price_type === 'free', fn($q) => $q->free())
            ->when($request->price_type === 'paid', fn($q) => $q->paid())
            ->when($request->price_min, fn($q) => $q->where('price', '>=', $request->price_min))
            ->when($request->price_max, fn($q) => $q->where('price', '<=', $request->price_max))
            ->when($request->sort_by, function($q) use ($request) {
                $sortBy = $request->sort_by;
                $sortOrder = $request->sort_order ?? 'asc';

                if (in_array($sortBy, ['title', 'price', 'created_at', 'updated_at'])) {
                    return $q->orderBy($sortBy, $sortOrder);
                } elseif ($sortBy === 'rating') {
                    return $q->orderBy('reviews_avg_rating', $sortOrder);
                } elseif ($sortBy === 'students_count') {
                    return $q->withCount('enrollments')->orderBy('enrollments_count', $sortOrder);
                } elseif ($sortBy === 'reviews_count') {
                    return $q->orderBy('reviews_count', $sortOrder);
                }

                return $q;
            }, function($q) {
                return $q->latest();
            })
            ->paginate($request->per_page ?? 15);

        return CourseResource::collection($courses);
    }

    public function store(CourseStoreRequest $request)
    {
        $this->authorize('create', Course::class);

        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? [];
            $instructorIds = $data['instructor_ids'] ?? [];
            unset($data['category_ids'], $data['instructor_ids']);

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

            $course->load(['categories', 'instructors']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course created successfully',
                'data' => new CourseResource($course)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create course'
            ], 500);
        }
    }

    public function show(Request $request, Course $course)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('view', $course);

        $reviewsLimit = $request->reviews_limit ?? 10;

        $course->load([
            'categories',
            'faqs',
            'sections.lessons',
            'instructors',
            'enrollments',
            'reviews' => function($q) use ($reviewsLimit) {
                $q->with('user.userProfile')
                    ->where('is_visible', true)
                    ->latest()
                    ->limit($reviewsLimit);
            }
        ]);

        $course->loadCount('reviews');
        $course->loadAvg('reviews', 'rating');

        return new CourseResource($course);
    }

    public function update(CourseUpdateRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? null;
            $instructorIds = $data['instructor_ids'] ?? null;
            unset($data['category_ids'], $data['instructor_ids']);

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

            $course->load(['categories', 'instructors']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course updated successfully',
                'data' => new CourseResource($course)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update course'
            ], 500);
        }
    }

    public function verify(CourseVerificationRequest $request, Course $course)
    {
        $this->authorize('verify', $course);

        try {
            if ($course->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft courses can be verified'
                ], 422);
            }

            $course->update([
                'status' => $request->status,
                'verified_at' => now(),
            ]);

            $course->load(['categories', 'instructors']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course verified successfully',
                'data' => new CourseResource($course)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify course'
            ], 500);
        }
    }

    public function reject(Request $request, Course $course)
    {
        $this->authorize('reject', $course);

        try {
            if ($course->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft courses can be rejected'
                ], 422);
            }

            $course->update([
                'status' => 'rejected',
                'verified_at' => now(),
            ]);

            $course->load(['categories', 'instructors']);

            return response()->json([
                'status' => 'success',
                'message' => 'Course rejected successfully',
                'data' => new CourseResource($course)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error rejecting course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject course'
            ], 500);
        }
    }

    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);

        try {
            $course->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Course deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting course: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete course'
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
