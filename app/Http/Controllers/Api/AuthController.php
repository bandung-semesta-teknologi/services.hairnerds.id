<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthForgotPasswordRequest;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\AuthResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
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

    public function register(AuthRegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

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

    public function forgotPassword(AuthForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email.'], 200)
            : response()->json(['message' => 'Unable to send reset link.'], 400);
    }

    public function resetPassword(AuthResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            },
        );
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset.'], 200)
            : response()->json(['message' => 'Invalid token or email.'], 400);
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return response()->json(['message' => 'Email verified successfully'], 201);
    }

    public function resendEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent!'], 200);
    }

    public function user(Request $request)
    {
        return response()->json(new UserResource($request->user), 200);
    }
}
