<?php

namespace App\Providers;

use App\Models\AnswerBank;
use App\Models\Attachment;
use App\Models\Bootcamp;
use App\Models\Category;
use App\Models\Course;
use App\Models\Faq;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Progress;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Review;
use App\Models\Section;
use App\Models\User;
use App\Policies\AnswerBankPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\BootcampPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\FaqPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\LessonPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProgressPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuizResultPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SectionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AnswerBank::class => AnswerBankPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        Bootcamp::class => BootcampPolicy::class,
        Category::class => CategoryPolicy::class,
        Course::class => CoursePolicy::class,
        Faq::class => FaqPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        Lesson::class => LessonPolicy::class,
        Payment::class => PaymentPolicy::class,
        Progress::class => ProgressPolicy::class,
        Question::class => QuestionPolicy::class,
        Quiz::class => QuizPolicy::class,
        QuizResult::class => QuizResultPolicy::class,
        Review::class => ReviewPolicy::class,
        Section::class => SectionPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
