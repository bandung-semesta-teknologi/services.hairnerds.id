<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function membershipSerial()
    {
        return $this->belongsTo(
            MembershipSerial::class,
            'serial_number',
            'serial_number'
        );
    }
}
