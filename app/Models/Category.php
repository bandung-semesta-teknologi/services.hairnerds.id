<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_categories');
    }

    public function bootcamps()
    {
        return $this->belongsToMany(Bootcamp::class, 'bootcamp_categories');
    }
}
