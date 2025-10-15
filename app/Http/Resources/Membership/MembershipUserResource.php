<?php

namespace App\Http\Resources\Membership;

use App\Http\Resources\CredentialResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $credentials = CredentialResource::collection($this->whenLoaded('userCredentials'));
        
        return [
            'id' => $this->id,
            'user_uuid_supabase' => $this->userProfile->user_uuid_supabase,
            'name' => $this->name,
            'credentials' => $credentials,
            'address' => $this->userProfile->address,
            'serial_number' => $this->membershipSerial->serial_number,
            'card_number' => $this->membershipSerial->card_number,
        ];
    }
}
