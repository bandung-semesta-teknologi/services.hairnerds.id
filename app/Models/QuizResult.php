<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_submitted' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $attributes = [
        'answered' => 0,
        'correct_answers' => 0,
        'total_obtained_marks' => 0,
        'is_submitted' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quizResult) {
            if (empty($quizResult->started_at)) {
                $quizResult->started_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('is_submitted', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('is_submitted', false);
    }

    public function scopePassed($query)
    {
        return $query->whereColumn('total_obtained_marks', '>=', 'quiz.pass_marks');
    }
}
