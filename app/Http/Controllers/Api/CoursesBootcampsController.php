<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bootcamp;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Resources\CoursesBootcampsResource;
use Illuminate\Pagination\LengthAwarePaginator;

class CoursesBootcampsController extends Controller
{
    public function index(Request $request)
    {
        $coursesQuery = Course::query()
            ->with(['instructors', 'categories', 'sections.lessons', 'reviews'])
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->published()
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->instructor_id, fn($q) => $q->whereHas('instructors', fn($q) => $q->where('users.id', $request->instructor_id)))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->level, fn($q) => $q->where('level', $request->level))
            ->when($request->price_type === 'free', fn($q) => $q->free())
            ->when($request->price_type === 'paid', fn($q) => $q->paid())
            ->when($request->price_min, fn($q) => $q->where('price', '>=', $request->price_min))
            ->when($request->price_max, fn($q) => $q->where('price', '<=', $request->price_max))
            ->when($request->is_highlight !== null, fn($q) => $q->where('is_highlight', $request->boolean('is_highlight')))
            ->when($request->rating, function($q) use ($request) {
                $rating = (int) $request->rating;
                return $q->having('reviews_avg_rating', '>=', $rating)
                         ->having('reviews_avg_rating', '<', $rating + 1);
            })
            ->latest();

        $bootcampsQuery = Bootcamp::query()
            ->with(['instructors', 'categories', 'faqs'])
            ->published()
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->instructor_id, fn($q) => $q->whereHas('instructors', fn($q) => $q->where('users.id', $request->instructor_id)))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->location, fn($q) => $q->where('location', 'like', '%' . $request->location . '%'))
            ->when($request->available !== null, function($q) use ($request) {
                return $request->boolean('available')
                    ? $q->where('seat_available', '>', 0)
                    : $q->where('seat_available', '=', 0);
            })
            ->when($request->price_type === 'free', fn($q) => $q->free())
            ->when($request->price_type === 'paid', fn($q) => $q->paid())
            ->when($request->price_min, fn($q) => $q->where('price', '>=', $request->price_min))
            ->when($request->price_max, fn($q) => $q->where('price', '<=', $request->price_max))
            ->latest();

        if ($request->type === 'course') {
            $courses = $coursesQuery->get()->map(function ($course) {
                $course->type = 'course';
                return $course;
            });
            $combined = $courses;
        } elseif ($request->type === 'bootcamp') {
            $bootcamps = $bootcampsQuery->get()->map(function ($bootcamp) {
                $bootcamp->type = 'bootcamp';
                return $bootcamp;
            });
            $combined = $bootcamps;
        } else {
            $courses = $coursesQuery->get()->map(function ($course) {
                $course->type = 'course';
                return $course;
            });

            $bootcamps = $bootcampsQuery->get()->map(function ($bootcamp) {
                $bootcamp->type = 'bootcamp';
                return $bootcamp;
            });

            $combined = $courses->concat($bootcamps);
        }

        if ($request->sort_by === 'price_asc') {
            $combined = $combined->sortBy('price')->values();
        } elseif ($request->sort_by === 'price_desc') {
            $combined = $combined->sortByDesc('price')->values();
        } elseif ($request->sort_by === 'title_asc') {
            $combined = $combined->sortBy('title')->values();
        } elseif ($request->sort_by === 'title_desc') {
            $combined = $combined->sortByDesc('title')->values();
        } else {
            $combined = $combined->sortByDesc('created_at')->values();
        }

        $perPage = $request->per_page ?? 15;
        $currentPage = $request->page ?? 1;
        $total = $combined->count();
        $items = $combined->forPage($currentPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return CoursesBootcampsResource::collection($paginator);
    }
}
