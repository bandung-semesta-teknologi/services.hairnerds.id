<?php

namespace App\Providers;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Policies\BootcampPolicy;
use App\Policies\CoursePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Course::class => CoursePolicy::class,
        Bootcamp::class => BootcampPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
