<?php

namespace App\Models;

use App\Enums\MembershipType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipSerial extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => MembershipType::class,
    ];

    public function getRouteKeyName(): string
    {
        return 'serial_number';
    }

    public function userProfile()
    {
        return $this->belongsTo(
            UserProfile::class,
            'used_by',
            'user_uuid_supabase'
        );
    }

    public function membershipTransactions()
    {
        return $this->hasMany(
            MembershipTransaction::class,
            'serial_number',
            'serial_number'
        );
    }
}
