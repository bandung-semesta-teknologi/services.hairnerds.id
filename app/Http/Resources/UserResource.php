<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->whenLoaded('userProfile');
        $credentials = CredentialResource::collection($this->whenLoaded('userCredentials'));

        return [
            'name' => $this->name,
            'address' => optional($profile)->address,
            'avatar' => optional($profile)->avatar,
            'date_of_birth' => optional($profile)->address,
            'credentials' => $credentials,
            'is_fully_verified' => (bool) collect($credentials->resolve())->every(fn($c) => $c['is_verified']),
        ];
    }
}
