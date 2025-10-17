<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollmentStoreRequest;
use App\Http\Requests\EnrollmentUpdateRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Enrollment::class);

        $user = $request->user();

        $enrollments = Enrollment::query()
            ->with([
                'user',
                'course.instructors',
                'course.lessons',
                'course.reviews' => function($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'progress.lesson'
            ])
            ->withCount([
                'progress as completed_lessons_count' => function($q) {
                    $q->where('is_completed', true);
                }
            ])
            ->addSelect([
                'last_activity_at' => DB::table('progress')
                    ->selectRaw('MAX(updated_at)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
            ])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->status === 'finished', fn($q) => $q->finished())
            ->when($request->status === 'active', fn($q) => $q->active())
            ->when($request->search, function($q) use ($request) {
                return $q->where(function($query) use ($request) {
                    $query->whereHas('user', function($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('email', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('course', function($q) use ($request) {
                        $q->where('title', 'like', '%' . $request->search . '%');
                    });
                });
            })
            ->latest('enrolled_at')
            ->paginate($request->per_page ?? 15);

        $courseIds = $enrollments->pluck('course_id')->unique();
        $userIds = $enrollments->pluck('user_id')->unique();

        $courseLessonCounts = DB::table('lessons')
            ->select('course_id', DB::raw('COUNT(*) as total_lessons'))
            ->whereIn('course_id', $courseIds)
            ->whereNull('deleted_at')
            ->groupBy('course_id')
            ->pluck('total_lessons', 'course_id');

        $courseQuizCounts = DB::table('lessons')
            ->join('quizzes', 'lessons.id', '=', 'quizzes.lesson_id')
            ->select('lessons.course_id', DB::raw('COUNT(DISTINCT lessons.id) as total_quizzes'))
            ->whereIn('lessons.course_id', $courseIds)
            ->whereNull('lessons.deleted_at')
            ->whereNull('quizzes.deleted_at')
            ->groupBy('lessons.course_id')
            ->pluck('total_quizzes', 'course_id');

        $completedQuizzes = DB::table('quiz_results')
            ->join('lessons', 'quiz_results.lesson_id', '=', 'lessons.id')
            ->select(
                'quiz_results.user_id',
                'lessons.course_id',
                DB::raw('COUNT(DISTINCT quiz_results.lesson_id) as completed_quizzes')
            )
            ->whereIn('quiz_results.user_id', $userIds)
            ->whereIn('lessons.course_id', $courseIds)
            ->where('quiz_results.is_submitted', true)
            ->groupBy('quiz_results.user_id', 'lessons.course_id')
            ->get()
            ->groupBy('user_id')
            ->map(function($group) {
                return $group->pluck('completed_quizzes', 'course_id');
            });

        $enrollments->getCollection()->transform(function ($enrollment) use ($courseLessonCounts, $courseQuizCounts, $completedQuizzes) {
            $courseId = $enrollment->course_id;
            $userId = $enrollment->user_id;

            $totalLessons = $courseLessonCounts->get($courseId, 0);
            $completedLessons = $enrollment->completed_lessons_count ?? 0;

            $totalQuizzes = $courseQuizCounts->get($courseId, 0);
            $completedQuizzesCount = $completedQuizzes->get($userId)?->get($courseId, 0) ?? 0;

            $completionPercentage = $totalLessons > 0
                ? (int) floor(($completedLessons / $totalLessons) * 100)
                : 0;

            $enrollment->total_lessons = $totalLessons;
            $enrollment->completed_lessons = $completedLessons;
            $enrollment->total_quizzes = $totalQuizzes;
            $enrollment->completed_quizzes = $completedQuizzesCount;
            $enrollment->completion_percentage = $completionPercentage;

            return $enrollment;
        });

        return EnrollmentResource::collection($enrollments);
    }


    public function store(EnrollmentStoreRequest $request)
    {
        $this->authorize('create', Enrollment::class);

        try {
            $data = $request->validated();
            $user = $request->user();

            if ($user->role === 'student') {
                $data['user_id'] = $user->id;
                $course = Course::findOrFail($data['course_id']);

                if ($course->status !== 'published') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot enroll in unpublished course'
                    ], 422);
                }

                $existingEnrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->exists();

                if ($existingEnrollment) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Already enrolled in this course'
                    ], 422);
                }
            }

            if ($user->role === 'instructor') {
                $course = Course::findOrFail($data['course_id']);

                if (!$course->instructors->contains($user)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot enroll students in courses you do not teach'
                    ], 403);
                }
            }

            $enrollment = Enrollment::create($data);
            $enrollment->load([
                'user',
                'course' => function($query) {
                    $query->with(['instructors.userProfile', 'categories']);
                }
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Enrollment created successfully',
                'data' => new EnrollmentResource($enrollment)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating enrollment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create enrollment'
            ], 500);
        }
    }

    public function show(Enrollment $enrollment)
    {
        $this->authorize('view', $enrollment);

        $enrollment->load([
            'user',
            'course' => function($query) {
                $query->with(['instructors.userProfile', 'categories']);
            },
            'progress'
        ]);

        return new EnrollmentResource($enrollment);
    }

    public function update(EnrollmentUpdateRequest $request, Enrollment $enrollment)
    {
        $this->authorize('update', $enrollment);

        try {
            $enrollment->update($request->validated());
            $enrollment->load([
                'user',
                'course' => function($query) {
                    $query->with(['instructors.userProfile', 'categories']);
                }
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Enrollment updated successfully',
                'data' => new EnrollmentResource($enrollment)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating enrollment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update enrollment'
            ], 500);
        }
    }

    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('delete', $enrollment);

        try {
            $enrollment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Enrollment deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting enrollment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete enrollment'
            ], 500);
        }
    }

    public function finish(Enrollment $enrollment)
    {
        $this->authorize('finish', $enrollment);

        try {
            $enrollment->update(['finished_at' => now()]);
            $enrollment->load([
                'user',
                'course' => function($query) {
                    $query->with(['instructors.userProfile', 'categories']);
                }
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Enrollment finished successfully',
                'data' => new EnrollmentResource($enrollment)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error finishing enrollment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to finish enrollment'
            ], 500);
        }
    }
}
