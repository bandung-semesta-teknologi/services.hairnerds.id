<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'raw_response_midtrans' => 'array',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected $attributes = [
        'amount' => 0,
        'tax' => 0,
        'discount' => 0,
        'total' => 0,
        'status' => 'pending',
        'payment_url' => null,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_code)) {
                $payment->payment_code = 'PAY-' . strtoupper(uniqid());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function isExpired(): bool
    {
        return $this->expired_at && now()->gt($this->expired_at);
    }

    public function markAsPaid($transactionId = null, $rawResponse = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'midtrans_transaction_id' => $transactionId,
            'raw_response_midtrans' => $rawResponse,
        ]);
    }

    public function markAsFailed($rawResponse = null)
    {
        $this->update([
            'status' => 'failed',
            'raw_response_midtrans' => $rawResponse,
        ]);
    }

    public function markAsExpired($rawResponse = null)
    {
        $this->update([
            'status' => 'expired',
            'raw_response_midtrans' => $rawResponse,
        ]);
    }
}
