<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdministratorStoreRequest;
use App\Http\Requests\AdministratorUpdateRequest;
use App\Http\Resources\AdministratorManagementResource;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdministratorManagementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAnyAdmin', User::class);

        $administrators = User::query()
            ->where('role', 'admin')
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

        return AdministratorManagementResource::collection($administrators);
    }

    public function store(AdministratorStoreRequest $request)
    {
        $this->authorize('createAdmin', User::class);

        try {
            return DB::transaction(function () use ($request) {
                $administrator = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ]);

                UserProfile::create([
                    'user_id' => $administrator->id,
                    'address' => null,
                    'avatar' => null,
                    'date_of_birth' => null,
                ]);

                UserCredential::create([
                    'user_id' => $administrator->id,
                    'type' => 'email',
                    'identifier' => $administrator->email,
                    'verified_at' => now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Administrator created successfully',
                    'data' => new AdministratorManagementResource($administrator)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating administrator', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create administrator',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(User $administrator)
    {
        $this->authorize('viewAdmin', [$administrator, $administrator]);

        if ($administrator->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an administrator'
            ], 404);
        }

        return new AdministratorManagementResource($administrator);
    }

    public function update(AdministratorUpdateRequest $request, User $administrator)
    {
        $this->authorize('updateAdmin', [$administrator, $administrator]);

        if ($administrator->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an administrator'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($request, $administrator) {
                $validated = $request->validated();

                $updateData = [];

                if (isset($validated['name'])) {
                    $updateData['name'] = $validated['name'];
                }

                if (isset($validated['email'])) {
                    $updateData['email'] = $validated['email'];
                }

                if (!empty($updateData)) {
                    $administrator->update($updateData);
                }

                if (isset($validated['email']) && $validated['email'] !== $administrator->getOriginal('email')) {
                    UserCredential::where('user_id', $administrator->id)
                        ->where('type', 'email')
                        ->update([
                            'identifier' => $validated['email'],
                        ]);
                }

                $administrator->refresh();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Administrator updated successfully',
                    'data' => new AdministratorManagementResource($administrator)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating administrator', [
                'administrator_id' => $administrator->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update administrator',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function resetPassword(User $administrator)
    {
        $this->authorize('resetPasswordAdmin', [$administrator, $administrator]);

        if ($administrator->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an administrator'
            ], 404);
        }

        try {
            $newPassword = Str::random(16);

            $administrator->update([
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
            Log::error('Error resetting administrator password', [
                'administrator_id' => $administrator->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset password',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy(User $administrator)
    {
        $this->authorize('deleteAdmin', [$administrator, $administrator]);

        if ($administrator->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not an administrator'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($administrator) {
                $administrator->userCredentials()->delete();

                $administrator->userProfile()->delete();

                $administrator->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Administrator and all related data deleted successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error deleting administrator', [
                'administrator_id' => $administrator->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete administrator',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
