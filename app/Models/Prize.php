<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Prize extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'redemption_start_date' => 'date',
        'redemption_end_date' => 'date',
    ];

    protected $attributes = [
        'status' => 'active',
        'available_stock' => 0,
        'blocked_stock' => 0,
        'used_stock' => 0,
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopePhysical($query)
    {
        return $query->where('type', 'physical');
    }

    public function scopePromoCode($query)
    {
        return $query->where('type', 'promo_code');
    }

    public function isAvailable(): bool
    {
        return $this->available_stock > 0;
    }

    public function hasStock(): bool
    {
        return $this->total_stock > 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRedemptionActive(): bool
    {
        $now = now();
        return $now->greaterThanOrEqualTo($this->redemption_start_date)
            && $now->lessThanOrEqualTo($this->redemption_end_date);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prize) {
            if (empty($prize->slug) && !empty($prize->name)) {
                $prize->slug = static::generateUniqueSlug($prize->name);
            }
        });

        static::updating(function ($prize) {
            if ($prize->isDirty('name') && !empty($prize->name)) {
                $prize->slug = static::generateUniqueSlug($prize->name, $prize->id);
            }
        });
    }

    public static function generateUniqueSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
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
