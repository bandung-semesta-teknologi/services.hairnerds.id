<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'store';

    protected $fillable = [
        'store_name',
        'address',
        'phone',
        'picture',
        'website',
        'id_owner',
        'social_facebook',
        'social_instagram',
        'social_twitter',
        'is_active',
        'latitude',
        'longitude',
        'delivery_charge',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function barber()
    {
        return $this->hasMany(Barber::class);
    }

    public function service()
    {
        return $this->hasMany(Service::class);
    }

    public function serviceCategory()
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function serviceBarber()
    {
        return $this->hasMany(ServiceBarber::class);
    }
}
