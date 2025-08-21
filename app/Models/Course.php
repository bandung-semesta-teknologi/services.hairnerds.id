<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });

        static::updating(function ($course) {
            if ($course->isDirty('title')) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'course_categories');
    }

    public function faqs()
    {
        return $this->hasMany(CourseFaq::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('sequence');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sequence');
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'course_instructures');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeNotPublished($query)
    {
        return $query->where('status', 'notpublished');
    }

    public function scopeTakedown($query)
    {
        return $query->where('status', 'takedown');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
