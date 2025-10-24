<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class MyBootcampResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bootcamp = $this->payable;

        return [
            'payment_id' => $this->id,
            'payment_code' => $this->payment_code,
            'ticket_id' => $this->payment_code,
            'bootcamp' => [
                'id' => $bootcamp->id,
                'title' => $bootcamp->title,
                'slug' => $bootcamp->slug,
                'description' => $bootcamp->description,
                'short_description' => $bootcamp->short_description,
                'thumbnail' => $bootcamp->thumbnail,
                'start_at' => $bootcamp->start_at,
                'end_at' => $bootcamp->end_at,
                'location' => $bootcamp->location,
                'url_location' => $bootcamp->url_location,
                'contact_person' => $bootcamp->contact_person,
                'price' => $bootcamp->price,
                'seat' => $bootcamp->seat,
                'seat_available' => $bootcamp->seat_available,
                'duration_days' => $bootcamp->start_at->diffInDays($bootcamp->end_at) + 1,
                'instructors' => UserResource::collection($this->whenLoaded('payable.instructors')),
                'categories' => CategoryResource::collection($this->whenLoaded('payable.categories')),
                'faqs' => $this->when(
                    $this->relationLoaded('payable') && $bootcamp->relationLoaded('faqs'),
                    function () use ($bootcamp) {
                        return $bootcamp->faqs->map(function ($faq) {
                            return [
                                'id' => $faq->id,
                                'question' => $faq->question,
                                'answer' => $faq->answer,
                            ];
                        });
                    }
                ),
            ],
            'enrollment_info' => [
                'enrolled_at' => $this->paid_at,
                'purchase_date' => $this->paid_at,
                'ticket_status' => $this->getTicketStatus(),
            ],
            'payment_info' => [
                'payment_method' => $this->payment_method,
                'amount' => $this->amount,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'total' => $this->total,
                'paid_at' => $this->paid_at,
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
