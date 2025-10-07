<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bootcamp extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'seat' => 0,
        'seat_available' => 0,
        'seat_blocked' => 0,
        'price' => 0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'bootcamp_categories');
    }

    public function faqs()
    {
        return $this->morphMany(Faq::class, 'faqable');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeUnpublished($query)
    {
        return $query->where('status', 'unpublish');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
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

    public function isAvailable()
    {
        return $this->seat_available > 0;
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function isPaid(): bool
    {
        return $this->price > 0;
    }

    public function hasAvailableSeats(): bool
    {
        return $this->seat_available > 0;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bootcamp) {
            if (empty($bootcamp->slug) && !empty($bootcamp->title)) {
                $bootcamp->slug = static::generateUniqueSlug($bootcamp->title);
            }
        });

        static::updating(function ($bootcamp) {
            if ($bootcamp->isDirty('title') && !empty($bootcamp->title)) {
                $bootcamp->slug = static::generateUniqueSlug($bootcamp->title, $bootcamp->id);
            }
        });
    }

    public static function generateUniqueSlug($title, $excludeId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, function($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
