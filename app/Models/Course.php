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
        'is_highlight' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'draft',
        'is_highlight' => false,
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

    public function getRouteKeyName()
    {
        return 'slug';
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

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
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

    public function scopeHighlight($query)
    {
        return $query->where('is_highlight', true);
    }

    public function scopeFree($query)
    {
        return $query->where(function($q) {
            $q->where('price', 0)->orWhereNull('price');
        });
    }

    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    public function isFree(): bool
    {
        return $this->price === 0 || $this->price === null;
    }

    public function isPaid(): bool
    {
        return $this->price > 0;
    }
}
