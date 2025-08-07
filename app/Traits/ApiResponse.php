<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

trait ApiResponse
{
    public function successResponse($data = null, $message = "", $code = 200): RedirectResponse
    {
        return RedirectResponse::json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    public function errorResponse($message = "", $code = 200): RedirectResponse
    {
        return RedirectResponse::json([
            'success' => false,
            'message' => $message,
        ], $code);
    }
}
