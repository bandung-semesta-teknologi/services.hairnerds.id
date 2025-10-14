<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyBootcampTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bootcamp = $this->payable;
        $user = $this->user;

        return [
            'ticket_id' => $this->payment_code,
            'qr_code_data' => $this->payment_code,
            'bootcamp' => [
                'id' => $bootcamp->id,
                'title' => $bootcamp->title,
                'thumbnail' => $bootcamp->thumbnail,
                'price' => $bootcamp->price,
                'start_at' => $bootcamp->start_at,
                'end_at' => $bootcamp->end_at,
                'location' => $bootcamp->location,
            ],
            'participant' => [
                'id' => $user->id,
                'name' => $this->user_name,
                'email' => $user->email,
                'avatar' => $user->userProfile?->avatar,
            ],
            'ticket_info' => [
                'ticket_for' => '1 person',
                'purchase_date' => $this->paid_at,
                'bootcamp_date' => $bootcamp->start_at->format('d F Y') . ' - ' . $bootcamp->end_at->format('d F Y'),
                'status' => $this->status,
                'ticket_status' => $this->getTicketStatus(),
            ],
            'payment_details' => [
                'payment_method' => $this->payment_method,
                'subtotal' => $this->amount,
                'tax' => $this->tax,
                'tax_percentage' => $this->amount > 0 ? round(($this->tax / $this->amount) * 100) : 0,
                'discount' => $this->discount,
                'discount_percentage' => $this->amount > 0 ? round(($this->discount / $this->amount) * 100) : 0,
                'total' => $this->total,
            ],
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

        $now = now();

        if ($now->greaterThan($bootcamp->end_at)) {
            return 'used';
        }

        if ($now->lessThan($bootcamp->start_at)) {
            return 'not_used';
        }

        return 'ongoing';
    }
}
