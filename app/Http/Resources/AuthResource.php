<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $accessExpireAt = now()->addDays(3);
        return [
            'token' => $this->user->createToken('access_token', ['*', $accessExpireAt])->plainTextToken,
            'token_expire_at' => $accessExpireAt,
            'token_type' => 'Bearer',
            'refresh_token' => $this->user->createToken('refresh_token', ['refresh'], $accessExpireAt->addDays(4))->plainTextToken,
            'user' => new UserResource($this->user),
        ];
    }
}
