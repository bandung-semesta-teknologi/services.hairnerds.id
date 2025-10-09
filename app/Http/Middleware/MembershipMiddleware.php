<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MembershipMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No token provided.'
                ], 401);
            }

            $this->validateToken($token);

            return $next($request);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Internal Server Error: {$e->getMessage()}"
            ], 500);
        }
    }

    private function validateToken(string $token): object
    {
        $jwtSecret = config('app.supabase.jwt_secret');

        if (!$jwtSecret) {
            throw new \Exception('JWT secret is not configured.');
        }

        return JWT::decode($token, new Key($jwtSecret, 'HS256'));
    }
}
