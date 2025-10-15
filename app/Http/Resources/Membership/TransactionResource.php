<?php

namespace App\Http\Resources\Membership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'payable' => $this->when($this->relationLoaded('payable'), function () {
                if ($this->payable_type === 'App\Models\MembershipTransaction') {
                    return [
                        'id' => $this->payable->id,
                        'merchant_id' => $this->payable->merchant_id,
                        'merchant_user_id' => $this->payable->merchant_user_id,
                        'merchant_name' => $this->payable->merchant_name,
                        'merchant_email' => $this->payable->merchant_email,
                        'user_id' => $this->payable->user_id,
                        'user_uuid_supabase' => $this->payable->user_uuid_supabase,
                        'serial_number' => $this->payable->serial_number,
                        'card_number' => $this->payable->card_number,
                        'name' => $this->payable->name,
                        'email' => $this->payable->email,
                        'address' => $this->payable->address,
                        'phone_number' => $this->payable->phone_number,
                        'total_amount' => $this->payable->total_amount,
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
