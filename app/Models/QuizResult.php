<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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

    public function isExpired(): bool
    {
        if (!$this->quiz || !$this->started_at) {
            return false;
        }

        $duration = $this->quiz->duration;
        if (!$duration) {
            return false;
        }

        $expectedFinishedAt = $this->getExpectedFinishedAt();

        return now()->gt($expectedFinishedAt);
    }

    public function getExpectedFinishedAt(): ?Carbon
    {
        if (!$this->quiz || !$this->started_at || !$this->quiz->duration) {
            return null;
        }

        $duration = $this->quiz->duration;
        if (is_string($duration)) {
            $durationParts = explode(':', $duration);
        } else {
            $durationParts = explode(':', $duration->format('H:i:s'));
        }

        $hours = (int) $durationParts[0];
        $minutes = (int) $durationParts[1];
        $seconds = (int) $durationParts[2];

        return $this->started_at
            ->copy()
            ->addHours($hours)
            ->addMinutes($minutes)
            ->addSeconds($seconds);
    }

    public function autoSubmit(): void
    {
        $this->update([
            'is_submitted' => true,
            'finished_at' => now(),
        ]);

        $enrollment = $this->user->enrollments()
            ->where('course_id', $this->quiz->course_id)
            ->first();

        if ($enrollment) {
            $enrollment->increment('quiz_attempts');
        }
    }
}
