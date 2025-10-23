<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'service';

    protected $fillable = [
        'gender',
        'name_service',
        'service_subtitle',
        'id_category',
        'description',
        'youtube_code',
        'price_type',
        'price_description',
        'allow_visible',
        'session_duration',
        'buffer_time',
        'image',
        'id_store',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function catalogCategory()
    {
        return $this->belongsTo(CatalogCategory::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
