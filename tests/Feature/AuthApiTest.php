<?php

use App\Models\User;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

describe('restful api authentication flow', function () {

    it('user can register', function () {
        postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'check@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201);
    });

    it('user can login and receive token', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'check@example.com',
        ]);

        postJson('/api/login', [
            'email' => 'check@example.com',
            'password' => 'password',
        ])
            ->assertStatus(200)
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
        $user = User::factory()->create();
        $loginResponse = postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $refreshResponse = withHeader('Authorization', 'Bearer ' . $refreshToken)
            ->postJson('/api/refresh-token');

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'token_expire_at',
                'token_type',
                'refresh_token',
            ]);
    });

    it('user can logout', function () {
        $user = User::factory()->create();
        $loginResponse = postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $accessToken = $loginResponse->json('token');

        $logoutResponse = withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['message' => 'Logout successfully']);
    });
});
