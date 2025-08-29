<?php

namespace App\Providers;

use App\Models\AnswerBank;
use App\Models\Bootcamp;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Review;
use App\Models\Section;
use App\Policies\AnswerBankPolicy;
use App\Policies\BootcampPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CourseFaqPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\LessonPolicy;
use App\Policies\ProgressPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuizResultPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SectionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Course::class => CoursePolicy::class,
        Bootcamp::class => BootcampPolicy::class,
        Section::class => SectionPolicy::class,
        Category::class => CategoryPolicy::class,
        CourseFaq::class => CourseFaqPolicy::class,
        Review::class => ReviewPolicy::class,
        Lesson::class => LessonPolicy::class,
        Quiz::class => QuizPolicy::class,
        Question::class => QuestionPolicy::class,
        AnswerBank::class => AnswerBankPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        Progress::class => ProgressPolicy::class,
        QuizResult::class => QuizResultPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
