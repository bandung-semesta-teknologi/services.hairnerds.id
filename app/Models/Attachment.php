<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function getFullUrlAttribute(): string
    {
        if (filter_var($this->url, FILTER_VALIDATE_URL)) {
            return $this->url;
        }

        return Storage::url($this->url);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (!filter_var($attachment->url, FILTER_VALIDATE_URL)) {
                if (Storage::exists($attachment->url)) {
                    Storage::delete($attachment->url);
                }
            }
        });
    }
}
