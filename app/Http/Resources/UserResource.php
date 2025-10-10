<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        $profile = $this->whenLoaded('userProfile');
        $credentials = CredentialResource::collection($this->whenLoaded('userCredentials'));
        $socials = SocialResource::collection($this->whenLoaded('socials'));

        $emailCredential = collect($credentials->resolve())->firstWhere('type', 'email');
        $phoneCredential = collect($credentials->resolve())->firstWhere('type', 'phone');

        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $phoneCredential['identifier'] ?? null,
            'role' => $this->role,
            'address' => optional($profile)->address,
            'avatar' => optional($profile)->avatar,
            'date_of_birth' => optional($profile)->date_of_birth,
            'short_biography' => optional($profile)->short_biography,
            'biography' => optional($profile)->biography,
            'skills' => optional($profile)->skills ?? [],
            'credentials' => $credentials,
            'socials' => $socials,
            'is_fully_verified' => (bool) collect($credentials->resolve())->every(fn($c) => $c['is_verified']),
        ];
    }
}
