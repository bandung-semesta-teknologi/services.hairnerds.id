<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CustomResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['userProfile', 'userCredentials', 'socials'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    public function userCredentials(): HasMany
    {
        return $this->hasMany(UserCredential::class);
    }

    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function socials(): HasMany
    {
        return $this->hasMany(Social::class);
    }

    public function courseInstructures()
    {
        return $this->belongsToMany(Course::class, 'course_instructures');
    }

    public function bootcampInstructors()
    {
        return $this->belongsToMany(Bootcamp::class, 'bootcamp_instructors');
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

    public function quizResults()
    {
        return $this->hasMany(QuizResult::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function membershipSerial()
    {
        return $this->hasOneThrough(
            MembershipSerial::class,
            UserProfile::class,
            'user_id',
            'used_by',
            'id',
            'user_uuid_supabase'
        )
            ->latest('used_at');
    }

    public function phoneNumberCredential()
    {
        return $this->hasOne(UserCredential::class)
            ->where('type', 'phone');
    }

    public function emailCredential()
    {
        return $this->hasOne(UserCredential::class)
            ->where('type', 'email');
    }
}
