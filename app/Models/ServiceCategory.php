<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'service_category';

    protected $fillable = [
        'name_category',
        'gender',
        'status',
        'sequence',
        'image',
        'id_store',
        'is_recommendation',
        'is_distance_matter',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
