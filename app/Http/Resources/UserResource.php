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
        $profile = $this->whenLoaded('profile');
        $credentials = $this->whenLoaded('credentials');

        return [
            'name' => $this->name,
            'address' => optional($profile)->address,
            'avatar' => optional($profile)->avatar,
            'date_of_birth' => optional($profile)->address,
            'credentials' => [
                'email' => $this->formatCredential($credentials, 'email'),
                'phone' => $this->formatCredential($credentials, 'phone'),
            ],
            'is_fully_verified' => (bool) $credentials->every(fn($c) => $c->verified_at !== null),
        ];
    }

    public function formatCredential($collection, $type)
    {
        $cred = $collection->where('type', $type)->first();

        if (!$cred) return null;

        return [
            'identifier' => $cred->identifier,
            'is_verified' => (bool) $cred->verified_at !== null,
            'verified_at' => optional($cred->verified_at)?->toISOString(),
        ];
    }
}
