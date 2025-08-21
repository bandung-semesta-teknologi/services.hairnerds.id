<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollmentStoreRequest;
use App\Http\Requests\EnrollmentUpdateRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $enrollments = Enrollment::query()
            ->with(['user', 'course', 'progress'])
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->status === 'finished', fn($q) => $q->finished())
            ->when($request->status === 'active', fn($q) => $q->active())
            ->latest('enrolled_at')
            ->paginate($request->per_page ?? 15);

        return EnrollmentResource::collection($enrollments);
    }

    public function store(EnrollmentStoreRequest $request)
    {
        try {
            $enrollment = Enrollment::create($request->validated());
            $enrollment->load(['user', 'course']);

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
        $enrollment->load(['user', 'course', 'progress']);

        return new EnrollmentResource($enrollment);
    }

    public function update(EnrollmentUpdateRequest $request, Enrollment $enrollment)
    {
        try {
            $enrollment->update($request->validated());
            $enrollment->load(['user', 'course']);

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
        try {
            $enrollment->update(['finished_at' => now()]);
            $enrollment->load(['user', 'course']);

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
