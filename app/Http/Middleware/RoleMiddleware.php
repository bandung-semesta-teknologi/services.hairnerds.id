<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
	public function handle(Request $request, Closure $next, ...$roles): Response
	{
		$user = $request->user();
		if (!$user) {
			return response()->json([
				'status' => 'error',
				'message' => 'Unauthenticated'
			], 401);
		}

		if (empty($roles)) {
			return response()->json([
				'status' => 'error',
				'message' => 'Forbidden'
			], 403);
		}

		if (!in_array($user->role, $roles, true)) {
			return response()->json([
				'status' => 'error',
				'message' => 'Forbidden'
			], 403);
		}

		return $next($request);
	}
}

