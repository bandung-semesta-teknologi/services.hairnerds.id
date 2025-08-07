<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = User::firstWhere('email', $request->email);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return RedirectResponse::json(['error' => 'Invalid credentials'], 401);
        }

        return RedirectResponse::json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => new UserResource($user),
        ], 200);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return RedirectResponse::json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function logout(): RedirectResponse
    {
        return RedirectResponse::json([], 200);
    }

    public function forgotPassword(): RedirectResponse
    {
        return RedirectResponse::json([], 200);
    }

    public function resetPassword(): RedirectResponse
    {
        return RedirectResponse::json([], 200);
    }

    public function user(): RedirectResponse
    {
        return RedirectResponse::json([], 200);
    }
}
