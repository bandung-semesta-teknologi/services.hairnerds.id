<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentUpdateRequest;
use App\Http\Resources\StudentManagementResource;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentManagementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $students = User::query()
            ->where('role', 'student')
            ->withCount('enrollments')
            ->when($request->search, function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->email_verified !== null, function($q) use ($request) {
                if ($request->boolean('email_verified')) {
                    $q->whereNotNull('email_verified_at');
                } else {
                    $q->whereNull('email_verified_at');
                }
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return StudentManagementResource::collection($students);
    }

    public function show(User $student)
    {
        $this->authorize('view', $student);

        if ($student->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not a student'
            ], 404);
        }

        $student->loadCount('enrollments');

        return new StudentManagementResource($student);
    }

    public function update(StudentUpdateRequest $request, User $student)
    {
        $this->authorize('update', $student);

        if ($student->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not a student'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($request, $student) {
                $data = $request->validated();

                $student->update($data);

                if (isset($data['email']) && $student->email !== $data['email']) {
                    $credential = UserCredential::where('user_id', $student->id)
                        ->where('type', 'email')
                        ->first();

                    if ($credential) {
                        $credential->update([
                            'identifier' => $data['email'],
                        ]);
                    }
                }

                $student->loadCount('enrollments');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Student updated successfully',
                    'data' => new StudentManagementResource($student)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating student: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update student'
            ], 500);
        }
    }

    public function resetPassword(User $student)
    {
        $this->authorize('resetPassword', $student);

        if ($student->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not a student'
            ], 404);
        }

        try {
            $newPassword = Str::random(16);

            $student->update([
                'password' => Hash::make($newPassword),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully',
                'data' => [
                    'new_password' => $newPassword,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error resetting student password: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset password'
            ], 500);
        }
    }

    public function destroy(User $student)
    {
        $this->authorize('delete', $student);

        if ($student->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not a student'
            ], 404);
        }

        try {
            $enrollmentsCount = $student->enrollments()->count();

            if ($enrollmentsCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete student with existing enrollments. Please remove enrollments first.'
                ], 422);
            }

            $student->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Student deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting student: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete student'
            ], 500);
        }
    }
}
