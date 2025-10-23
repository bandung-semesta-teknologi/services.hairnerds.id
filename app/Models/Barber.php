<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barber extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'barber';

    protected $fillable = [
        'id_user',
        'id_store',
        'email',
        'full_name',
        'color',
        'phone',
        'is_active',
        'sync_status',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function serviceBarber()
    {
        return $this->hasMany(ServiceBarber::class);
    }
}
