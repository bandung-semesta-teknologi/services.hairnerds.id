<?php

use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

describe('restful api authentication flow', function () {

    it('user can register', function () {
        postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'check@example.com',
            'phone' => '6281234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201);
    });

    it('email verification is sent after registration', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    it('user verifying email via signed URL', function () {
        $user = User::factory()->unverified()->create();

        $userCredential = UserCredential::factory()->emailCredential()->unverified()->create([
            'user_id' => $user->id,
            'identifier' => $user->email,
        ]);

        actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        get($url)->assertRedirect();

        expect($user->fresh()->email_verified_at)->not->toBeNull();
        expect($userCredential->fresh()->verified_at)->not->toBeNull();
    });

    it('user can resend verification email', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJson(['message' => 'Verification link sent!']);

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    it('user can login by email and receive token', function () {
        $user = UserCredential::factory()->emailCredential()->create();

        postJson('/api/login', [
            'type' => 'email',
            'identifier' => $user->identifier,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_expire_at',
                'token_type',
                'refresh_token',
                'user' => [
                    'name',
                    'email',
                ],
            ]);
    });

    it('user can login by phone and receive token', function () {
        $user = UserCredential::factory()->phoneCredential()->create();

        postJson('/api/login', [
            'type' => 'phone',
            'identifier' => $user->identifier,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_expire_at',
                'token_type',
                'refresh_token',
                'user' => [
                    'name',
                    'email',
                ],
            ]);
    });

    it('user can refresh token', function () {
        $user = UserCredential::factory()->emailCredential()->create();
        $loginResponse = postJson('/api/login', [
            'type' => 'email',
            'identifier' => $user->identifier,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $refreshResponse = withHeader('Authorization', 'Bearer ' . $refreshToken)
            ->postJson('/api/refresh-token');

        $refreshResponse->assertOk()
            ->assertJsonStructure([
                'token',
                'token_expire_at',
                'token_type',
                'refresh_token',
            ]);
    });

    it('user can logout', function () {
        $user = UserCredential::factory()->emailCredential()->create();
        $loginResponse = postJson('/api/login', [
            'type' => 'email',
            'identifier' => $user->identifier,
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('token');

        $logoutResponse = withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/logout');

        $logoutResponse->assertOk()
            ->assertJson(['message' => 'Logout successfully']);
    });

    it('user can request password reset link', function () {
        Notification::fake();

        $user = User::factory()->create();

        $response = postJson('/api/forgot-password', ['email' => $user->email]);

        $response->assertOk()
            ->assertJson(['message' => 'Reset link sent to your email.']);

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('user can reset password with valid token', function () {
        Event::fake();

        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertOk();
        expect(Hash::check('new-password', $user->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    });
});
