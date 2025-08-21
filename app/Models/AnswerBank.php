<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswerBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_true' => 'boolean',
    ];

    protected $attributes = [
        'is_true' => false,
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function scopeCorrect($query)
    {
        return $query->where('is_true', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_true', false);
    }
}
