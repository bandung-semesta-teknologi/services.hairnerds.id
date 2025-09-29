<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'datetime' => 'datetime',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    public function quizResults()
    {
        return $this->hasMany(QuizResult::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }
}
