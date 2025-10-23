<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\CourseStudentProgressRequest;
use App\Http\Resources\Academy\CourseStudentProgressResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseStudentProgressController extends Controller
{
    public function index(CourseStudentProgressRequest $request, Course $course)
    {
        $this->authorize('viewStudentProgress', $course);

        $search = $request->string('search')->toString();
        $sortBy = $request->string('sort_by', 'enrolled_at')->toString();
        $sortOrder = strtolower($request->string('sort_order', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $totalLessons = $course->lessons()->count();
        $totalQuizzes = $course->lessons()->whereHas('quiz')->count();

        $query = Enrollment::query()
            ->where('course_id', $course->id)
            ->select('enrollments.*')
            ->with(['user'])
            ->addSelect([
                'last_seen_at' => Progress::selectRaw('MAX(updated_at)')
                    ->whereColumn('user_id', 'enrollments.user_id')
                    ->where('course_id', $course->id)
            ])
            ->withCount([
                'progress as completed_lessons_count' => function ($q) use ($course) {
                    $q->where('course_id', $course->id)->where('is_completed', true);
                },
            ])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            });

        if ($sortBy === 'name') {
            $query->orderBy(
                DB::raw('(select name from users where users.id = enrollments.user_id)'),
                $sortOrder
            );
        } elseif ($sortBy === 'progress') {
            $query->orderBy('completed_lessons_count', $sortOrder);
        } elseif ($sortBy === 'completed_at') {
            $query->orderBy('finished_at', $sortOrder);
        } else {
            $query->orderBy('enrolled_at', $sortOrder);
        }

        $items = $query->get();

        $items->transform(function ($enrollment) use ($totalLessons, $totalQuizzes, $course) {
            $enrollment->setAttribute('total_lessons', $totalLessons);
            $enrollment->setAttribute('total_quizzes', $totalQuizzes);

            $quizTaken = \App\Models\QuizResult::query()
                ->where('user_id', $enrollment->user_id)
                ->submitted()
                ->whereHas('lesson', function ($q) use ($course) {
                    $q->where('course_id', $course->id);
                })
                ->count();

            $enrollment->setAttribute('quiz_taken', $quizTaken);

            return $enrollment;
        });

        return CourseStudentProgressResource::collection($items);
    }
}


