<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BootcampEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'student' => [
                'id' => $this->user_id,
                'name' => $this->user_name,
                'email' => $this->user?->email,
                'avatar' => $this->user?->userProfile?->avatar,
            ],
            'enrolled_at' => $this->paid_at,
            'ticket_status' => $this->getTicketStatus(),
            'payment_status' => $this->status,
            'amount_paid' => $this->total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getTicketStatus(): string
    {
        $bootcamp = $this->payable;

        if (!$bootcamp) {
            return 'unknown';
        }

        if (now()->greaterThan($bootcamp->end_at)) {
            return 'used';
        }

        if (now()->lessThan($bootcamp->start_at)) {
            return 'not_used';
        }

        return 'ongoing';
    }
}
