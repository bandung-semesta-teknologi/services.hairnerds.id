<?php

namespace App\Http\Resources\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'payable' => $this->when($this->relationLoaded('payable'), function () {
                if ($this->payable_type === 'App\Models\Course') {
                    return [
                        'id' => $this->payable->id,
                        'title' => $this->payable->title,
                        'slug' => $this->payable->slug,
                        'price' => $this->payable->price,
                        'thumbnail' => $this->payable->thumbnail,
                    ];
                } elseif ($this->payable_type === 'App\Models\Bootcamp') {
                    return [
                        'id' => $this->payable->id,
                        'title' => $this->payable->title,
                        'slug' => $this->payable->slug,
                        'price' => $this->payable->price,
                        'location' => $this->payable->location,
                        'start_date' => $this->payable->start_date,
                        'end_date' => $this->payable->end_date,
                    ];
                }
                return null;
            }),
            'payment_code' => $this->payment_code,
            'payment_method' => $this->payment_method,
            'payment_url' => $this->payment_url,
            'amount' => $this->amount,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'total' => $this->total,
            'status' => $this->status,
            'midtrans_transaction_id' => $this->midtrans_transaction_id,
            'paid_at' => $this->paid_at,
            'expired_at' => $this->expired_at,
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
