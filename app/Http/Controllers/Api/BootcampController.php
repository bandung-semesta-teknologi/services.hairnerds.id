<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BootcampStoreRequest;
use App\Http\Requests\BootcampUpdateRequest;
use App\Http\Requests\BootcampVerificationRequest;
use App\Http\Resources\BootcampResource;
use App\Models\Bootcamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BootcampController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalUser($request);

        $this->authorize('viewAny', Bootcamp::class);

        $bootcamps = Bootcamp::query()
            ->with(['user', 'categories', 'faqs'])
            ->when($user && $user->role === 'instructor', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->when(!$user || $user->role === 'student', fn($q) => $q->published())
            ->when($request->category_id, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id)))
            ->when($request->status && $user && $user->role === 'admin', fn($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
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
            unset($data['category_ids']);

            if (isset($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
            }

            if (!isset($data['user_id'])) {
                $data['user_id'] = $request->user()->id;
            }

            if (!isset($data['seat_available'])) {
                $data['seat_available'] = $data['seat'];
            }

            $bootcamp = Bootcamp::create($data);

            if (!empty($categoryIds)) {
                $bootcamp->categories()->attach($categoryIds);
            }

            $bootcamp->load(['user', 'categories']);

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

        $this->authorize('view', $bootcamp);

        $bootcamp->load(['user', 'categories', 'faqs']);

        return new BootcampResource($bootcamp);
    }

    public function update(BootcampUpdateRequest $request, Bootcamp $bootcamp)
    {
        $this->authorize('update', $bootcamp);

        try {
            $data = $request->validated();
            $categoryIds = $data['category_ids'] ?? null;
            unset($data['category_ids']);

            if (isset($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $request->file('thumbnail')->store('bootcamps/thumbnails', 'public');
            }

            if ($bootcamp->status === 'rejected' && !isset($data['status'])) {
                $data['status'] = 'draft';
                $data['verified_at'] = null;
            }

            $bootcamp->update($data);

            if ($categoryIds !== null) {
                $bootcamp->categories()->sync($categoryIds);
            }

            $bootcamp->load(['user', 'categories']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bootcamp updated successfully',
                'data' => new BootcampResource($bootcamp)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating bootcamp: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bootcamp'
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

            $bootcamp->load(['user', 'categories']);

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

            $bootcamp->load(['user', 'categories']);

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
