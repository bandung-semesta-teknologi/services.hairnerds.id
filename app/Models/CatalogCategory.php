<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'catalog_category';

    protected $fillable = [
        'category_name',
        'picture',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function service()
    {
        return $this->hasMany(Service::class);
    }
}
