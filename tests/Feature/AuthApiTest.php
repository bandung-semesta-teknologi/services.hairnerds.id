<?php

use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

describe('restful api authentication flow', function () {
    $validLoginJsonResponse = [
        'token',
        'token_expire_at',
        'token_type',
        'refresh_token',
        'user' => [
            'name',
            'address',
            'avatar',
            'date_of_birth',
            'credentials',
            'is_fully_verified',
        ],
    ];

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

        $userCredential = UserCredential::factory()
            ->for($user)
            ->emailCredential($user->email)
            ->unverified()
            ->create();

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

        actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJson(['message' => 'Verification link sent!']);

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    it('user can login by email and receive token', function () use ($validLoginJsonResponse) {
        $user = UserCredential::factory()->emailCredential()->create();

        postJson('/api/login', [
            'type' => 'email',
            'identifier' => $user->identifier,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure($validLoginJsonResponse);
    });

    it('user can login by phone and receive token', function () use ($validLoginJsonResponse) {
        $user = UserCredential::factory()->phoneCredential()->create();

        postJson('/api/login', [
            'type' => 'phone',
            'identifier' => $user->identifier,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure($validLoginJsonResponse);
    });

    it('user can refresh token', function () {
        $user = UserCredential::factory()->emailCredential()->create();

        $loginResponse = postJson('/api/login', [
            'type' => 'email',
            'identifier' => $user->identifier,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        withHeader('Authorization', 'Bearer ' . $refreshToken)
            ->postJson('/api/refresh-token')
            ->assertOk()
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

        withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logout successfully']);
    });

    it('user can request password reset link', function () {
        Notification::fake();

        $user = User::factory()->create();

        $response = postJson('/api/forgot-password', ['email' => $user->email]);

        $response->assertOk()
            ->assertJson(['message' => 'Reset link sent to your email.']);

        Notification::assertSentTo($user, CustomResetPasswordNotification::class);
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

    it('check if user is verified', function () {
        $verifiedUser = User::factory()
            ->has(UserProfile::factory())
            ->has(UserCredential::factory()->emailCredential())
            ->has(UserCredential::factory()->phoneCredential('6281234567890'))
            ->create();

        postJson('/api/login', [
            'type' => 'email',
            'identifier' => $verifiedUser->userCredentials()->firstWhere('type', 'email')->identifier,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.is_fully_verified', true);
    });

    it('check if user is unverified', function () {
        postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'check@example.com',
            'phone' => '6281234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201);

        postJson('/api/login', [
            'type' => 'email',
            'identifier' => 'check@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.is_fully_verified', false);
    });

    it('user can update profile and upload avatar', function () {
        Storage::fake('public');

        $user = User::factory()
            ->has(UserProfile::factory())
            ->has(UserCredential::factory()->emailCredential())
            ->has(UserCredential::factory()->phoneCredential())
            ->create();

        $loginResponse = postJson('/api/login', [
            'type' => 'email',
            'identifier' => $user->userCredentials()->firstWhere('type', 'email')->identifier,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('token');

        $file = UploadedFile::fake()->image('avatar.jpg');

        withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user', [
                'name' => 'John Doe',
                'address' => 'Rumah Baru',
                'date_of_birth' => now()->subYears(20)->toDateString(),
                'avatar' => $file
            ])->assertStatus(200);

        $user->refresh();

        expect($user->name)->toBe('John Doe')
            ->and($user->userProfile->address)->toBe('Rumah Baru')
            ->and($user->userProfile->date_of_birth)->toBe(now()->subYears(20)->toDateString())
            ->and($user->userProfile->avatar)->not()->toBeNull();

        Storage::disk('public')->assertExists($user->userProfile->avatar);
    });
});
