<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BootcampStoreRequest;
use App\Http\Requests\BootcampUpdateRequest;
use App\Http\Requests\BootcampVerificationRequest;
use App\Http\Resources\BootcampResource;
use App\Http\Resources\BootcampEnrollmentResource;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Gate;
use App\Models\Payment;
use App\Models\Bootcamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BootcampController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', Bootcamp::class);

        $bootcamps = Bootcamp::query()
            ->with(['instructors', 'categories', 'faqs'])
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when(!$user || $user->role === 'student', fn($q) => $q->published())
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->status && $user && $user->role === 'admin', fn($q) => $q->where('status', $request->status))
            ->when($request->instructor_id, fn($q) => $q->whereHas('instructors', fn($q) => $q->where('users.id', $request->instructor_id)))
            ->when($request->location, fn($q) => $q->where('location', 'like', '%' . $request->location . '%'))
            ->when($request->available !== null, function($q) use ($request) {
                return $request->boolean('available')
                    ? $q->where('seat_available', '>', 0)
                    : $q->where('seat_available', '=', 0);
            })
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->price_type === 'free', fn($q) => $q->free())
            ->when($request->price_type === 'paid', fn($q) => $q->paid())
            ->when($request->price_min, fn($q) => $q->where('price', '>=', $request->price_min))
            ->when($request->price_max, fn($q) => $q->where('price', '<=', $request->price_max))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return BootcampResource::collection($bootcamps);
    }

    public function store(BootcampStoreRequest $request)
    {
        $this->authorize('create', Bootcamp::class);

        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? [];
            $instructorIds = $data['instructor_ids'] ?? [];
            unset($data['category_ids'], $data['instructor_ids']);

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
            }

            if (!isset($data['seat_available'])) {
                $data['seat_available'] = $data['seat'];
            }

            $bootcamp = Bootcamp::create($data);

            if (!empty($categories)) {
                $categoryIds = $this->categoryService->resolveCategoryIds($categories);
                $bootcamp->categories()->attach($categoryIds);
            }

            if (!empty($instructorIds)) {
                $bootcamp->instructors()->attach($instructorIds);
            }

            $bootcamp->load(['instructors', 'categories']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bootcamp created successfully',
                'data' => new BootcampResource($bootcamp)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating bootcamp: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bootcamp'
            ], 500);
        }
    }

    public function show(Request $request, Bootcamp $bootcamp)
    {
        $user = $this->resolveOptionalUser($request);

        if (!\Gate::forUser($user)->allows('view', $bootcamp)) {
            abort(403, 'This action is unauthorized.');
        }

        $bootcamp->load(['instructors', 'categories', 'faqs']);

        return new BootcampResource($bootcamp);
    }

    public function update(BootcampUpdateRequest $request, Bootcamp $bootcamp)
    {
        $this->authorize('update', $bootcamp);

        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? null;
            $instructorIds = $data['instructor_ids'] ?? null;
            unset($data['category_ids'], $data['instructor_ids']);

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
            }

            if ($bootcamp->status === 'rejected' && !isset($data['status'])) {
                $data['status'] = 'draft';
                $data['verified_at'] = null;
            }

            $bootcamp->update($data);

            if ($categories !== null) {
                $categoryIds = $this->categoryService->resolveCategoryIds($categories);
                $bootcamp->categories()->sync($categoryIds);
            }

            if ($instructorIds !== null) {
                $bootcamp->instructors()->sync($instructorIds);
            }

            $bootcamp->load(['instructors', 'categories']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bootcamp updated successfully',
                'data' => new BootcampResource($bootcamp)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating bootcamp: ' . $e->getMessage(), [
                'bootcamp_id' => $bootcamp->id,
                'user_id' => $request->user()?->id,
                'data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bootcamp',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function verify(BootcampVerificationRequest $request, Bootcamp $bootcamp)
    {
        $this->authorize('verify', $bootcamp);

        try {
            if ($bootcamp->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft bootcamps can be verified'
                ], 422);
            }

            $bootcamp->update([
                'status' => $request->status,
                'verified_at' => now(),
            ]);

            $bootcamp->load(['instructors', 'categories']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bootcamp verified successfully',
                'data' => new BootcampResource($bootcamp)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying bootcamp: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify bootcamp'
            ], 500);
        }
    }

    public function reject(Request $request, Bootcamp $bootcamp)
    {
        $this->authorize('reject', $bootcamp);

        try {
            if ($bootcamp->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft bootcamps can be rejected'
                ], 422);
            }

            $bootcamp->update([
                'status' => 'rejected',
                'verified_at' => now(),
            ]);

            $bootcamp->load(['instructors', 'categories']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bootcamp rejected successfully',
                'data' => new BootcampResource($bootcamp)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error rejecting bootcamp: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject bootcamp'
            ], 500);
        }
    }

    public function destroy(Bootcamp $bootcamp)
    {
        $this->authorize('delete', $bootcamp);

        try {
            $bootcamp->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bootcamp deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting bootcamp: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete bootcamp'
            ], 500);
        }
    }

    public function enrolledStudents(Request $request, Bootcamp $bootcamp)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('view', $bootcamp);

        $enrollments = Payment::query()
            ->with(['user.userProfile', 'payable'])
            ->where('payable_type', Bootcamp::class)
            ->where('payable_id', $bootcamp->id)
            ->where('status', 'paid')
            ->when($request->search, function($q) use ($request) {
                return $q->where(function($query) use ($request) {
                    $query->where('user_name', 'like', '%' . $request->search . '%')
                        ->orWhereHas('user', function($q) use ($request) {
                            $q->where('email', 'like', '%' . $request->search . '%');
                        });
                });
            })
            ->when($request->ticket_status, function($q) use ($request, $bootcamp) {
                $status = $request->ticket_status;
                $now = now();

                if ($status === 'used' && $bootcamp->end_at < $now) {
                    return $q;
                } elseif ($status === 'not_used' && $bootcamp->start_at > $now) {
                    return $q;
                } elseif ($status === 'ongoing' && $bootcamp->start_at <= $now && $bootcamp->end_at >= $now) {
                    return $q;
                } else {
                    return $q->whereRaw('1 = 0');
                }
            })
            ->orderBy('paid_at', 'desc')

            ->paginate($request->per_page ?? 15);

        return BootcampEnrollmentResource::collection($enrollments);
    }

    public function enrolledStudentsStats(Bootcamp $bootcamp)
    {
        $this->authorize('view', $bootcamp);

        $totalEnrolled = Payment::where('payable_type', Bootcamp::class)
            ->where('payable_id', $bootcamp->id)
            ->where('status', 'paid')
            ->count();

        $now = now();
        $usedTickets = 0;
        $notUsedTickets = 0;
        $ongoingTickets = 0;

        if ($bootcamp->end_at < $now) {
            $usedTickets = $totalEnrolled;
        } elseif ($bootcamp->start_at > $now) {
            $notUsedTickets = $totalEnrolled;
        } else {
            $ongoingTickets = $totalEnrolled;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_enrolled' => $totalEnrolled,
                'used_tickets' => $usedTickets,
                'not_used_tickets' => $notUsedTickets,
                'ongoing_tickets' => $ongoingTickets,
                'revenue' => Payment::where('payable_type', Bootcamp::class)
                    ->where('payable_id', $bootcamp->id)
                    ->where('status', 'paid')
                    ->sum('total'),
            ]
        ]);
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
