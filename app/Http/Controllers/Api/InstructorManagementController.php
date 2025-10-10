<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstructorStoreRequest;
use App\Http\Requests\InstructorUpdateRequest;
use App\Http\Resources\InstructorManagementResource;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstructorManagementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $instructors = User::query()
            ->where('role', 'instructor')
            ->withCount('courseInstructures')
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

        return InstructorManagementResource::collection($instructors);
    }

    public function store(InstructorStoreRequest $request)
    {
        $this->authorize('create', User::class);

        try {
            return DB::transaction(function () use ($request) {
                $instructor = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'instructor',
                    'email_verified_at' => now(),
                ]);

                UserProfile::create([
                    'user_id' => $instructor->id,
                    'address' => null,
                    'avatar' => null,
                    'date_of_birth' => null,
                ]);

                UserCredential::create([
                    'user_id' => $instructor->id,
                    'type' => 'email',
                    'identifier' => $instructor->email,
                    'verified_at' => now(),
                ]);

                $instructor->loadCount('courseInstructures');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Instructor created successfully',
                    'data' => new InstructorManagementResource($instructor)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating instructor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create instructor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(User $instructor)
    {
        $this->authorize('view', $instructor);

        if ($instructor->role !== 'instructor') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an instructor'
            ], 404);
        }

        $instructor->loadCount('courseInstructures');

        return new InstructorManagementResource($instructor);
    }

    public function update(InstructorUpdateRequest $request, User $instructor)
    {
        $this->authorize('update', $instructor);

        if ($instructor->role !== 'instructor') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an instructor'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($request, $instructor) {
                $validated = $request->validated();

                $updateData = [];

                if (isset($validated['name'])) {
                    $updateData['name'] = $validated['name'];
                }

                if (isset($validated['email'])) {
                    $updateData['email'] = $validated['email'];
                }

                if (!empty($updateData)) {
                    $instructor->update($updateData);
                }

                if (isset($validated['email']) && $validated['email'] !== $instructor->getOriginal('email')) {
                    UserCredential::where('user_id', $instructor->id)
                        ->where('type', 'email')
                        ->update([
                            'identifier' => $validated['email'],
                        ]);
                }

                $instructor->refresh();
                $instructor->loadCount('courseInstructures');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Instructor updated successfully',
                    'data' => new InstructorManagementResource($instructor)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating instructor', [
                'instructor_id' => $instructor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update instructor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function resetPassword(User $instructor)
    {
        $this->authorize('resetPassword', $instructor);

        if ($instructor->role !== 'instructor') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an instructor'
            ], 404);
        }

        try {
            $newPassword = Str::random(16);

            $instructor->update([
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
            Log::error('Error resetting instructor password', [
                'instructor_id' => $instructor->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset password',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy(User $instructor)
    {
        $this->authorize('delete', $instructor);

        if ($instructor->role !== 'instructor') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an instructor'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($instructor) {
                $instructor->reviews()->delete();

                $instructor->courseInstructures()->detach();

                $instructor->bootcampInstructors()->detach();

                $instructor->userCredentials()->delete();

                $instructor->userProfile()->delete();

                $instructor->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Instructor and all related data deleted successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error deleting instructor', [
                'instructor_id' => $instructor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete instructor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
