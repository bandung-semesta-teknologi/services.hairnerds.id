<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user->tokens()->delete();

        $accessExpireAt = now()->addDays(3);
        $accessToken = $user->createToken('access_token', ['*', $accessExpireAt])->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], $accessExpireAt->addDays(4))->plainTextToken;

        return response()->json([
            'token' => $accessToken,
            'token_expire_at' => $accessExpireAt,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
            'user' => new UserResource($user),
        ], 200);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Registered Successfully'], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout successfully'], 200);
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = PersonalAccessToken::findToken($request->bearerToken());

        if (!$refreshToken || !$refreshToken->can('refresh') || $refreshToken->expires_at->isPast()) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401);
        }

        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        $accessExpireAt = now()->addDays(3);
        $accessToken = $user->createToken('access_token', ['*', $accessExpireAt])->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], $accessExpireAt->addDays(4))->plainTextToken;

        return response()->json([
            'token' => $accessToken,
            'token_expire_at' => $accessExpireAt,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
        ], 200);
    }

    public function forgotPassword()
    {
        return response()->json([], 200);
    }

    public function resetPassword()
    {
        return response()->json([], 200);
    }

    public function user(Request $request)
    {
        return response()->json(new UserResource($request->user), 200);
    }
}
