<?php

use App\Http\Controllers\Api\AnswerBankController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\BootcampController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseFaqController;
use App\Http\Controllers\Api\CourseStudentProgressController;
use App\Http\Controllers\Api\CourseWithFaqController;
use App\Http\Controllers\Api\CurriculumController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\InstructorManagementController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizLessonController;
use App\Http\Controllers\Api\QuizResultController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\StudentManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('academy')->name('academy.')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/faqs', [FaqController::class, 'index']);
    Route::get('/faqs/{faq}', [FaqController::class, 'show']);

    Route::get('/sections', [SectionController::class, 'index']);
    Route::get('/sections/{section}', [SectionController::class, 'show']);

    Route::get('/bootcamps', [BootcampController::class, 'index']);
    Route::get('/bootcamps/{bootcamp}', [BootcampController::class, 'show']);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);

    Route::post('/payments/callback', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::get('/payments/finish', [PaymentController::class, 'finish'])->name('payment.finish');
    Route::post('/payments/generate-signature', [PaymentController::class, 'generateSignature']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/instructors', [InstructorController::class, 'index']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/courses/{course:slug}/student-progress', [CourseStudentProgressController::class, 'index']);

        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{course:slug}', [CourseController::class, 'update']);
        Route::delete('/courses/{course:slug}', [CourseController::class, 'destroy']);
        Route::post('/courses/{course:slug}/verify', [CourseController::class, 'verify']);
        Route::post('/courses/{course:slug}/reject', [CourseController::class, 'reject']);

        Route::post('/curriculum', [CurriculumController::class, 'store']);
        Route::put('/curriculum/{section}', [CurriculumController::class, 'update']);
        Route::post('/curriculum/{section}', [CurriculumController::class, 'updateViaPost']);

        Route::post('/courses-with-faqs', [CourseWithFaqController::class, 'store']);
        Route::put('/courses-with-faqs/{course:slug}', [CourseWithFaqController::class, 'update']);

        Route::post('/bootcamps', [BootcampController::class, 'store']);
        Route::put('/bootcamps/{bootcamp}', [BootcampController::class, 'update']);
        Route::delete('/bootcamps/{bootcamp}', [BootcampController::class, 'destroy']);
        Route::post('/bootcamps/{bootcamp}/verify', [BootcampController::class, 'verify']);
        Route::post('/bootcamps/{bootcamp}/reject', [BootcampController::class, 'reject']);

        Route::post('/faqs', [FaqController::class, 'store']);
        Route::put('/faqs/{faq}', [FaqController::class, 'update']);
        Route::delete('/faqs/{faq}', [FaqController::class, 'destroy']);

        Route::post('/sections/update-sequence', [SectionController::class, 'updateSequence']);

        Route::post('/sections', [SectionController::class, 'store']);
        Route::put('/sections/{section}', [SectionController::class, 'update']);
        Route::delete('/sections/{section}', [SectionController::class, 'destroy']);

        Route::get('/lessons', [LessonController::class, 'index']);
        Route::post('/lessons', [LessonController::class, 'store']);
        Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
        Route::post('/lessons/{lesson}', [LessonController::class, 'update']);
        Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy']);

        Route::post('/quiz-lessons', [QuizLessonController::class, 'store']);
        Route::put('/quiz-lessons/{lesson}', [QuizLessonController::class, 'update']);

        Route::get('/attachments', [AttachmentController::class, 'index']);
        Route::post('/attachments', [AttachmentController::class, 'store']);
        Route::post('/attachments/bulk', [AttachmentController::class, 'bulkStore']);
        Route::get('/attachments/{attachment}', [AttachmentController::class, 'show']);
        Route::post('/attachments/{attachment}', [AttachmentController::class, 'update']);
        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

        Route::apiResource('quizzes', QuizController::class);
        Route::apiResource('questions', QuestionController::class);
        Route::apiResource('answer-banks', AnswerBankController::class);

        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        Route::apiResource('enrollments', EnrollmentController::class);
        Route::post('/enrollments/{enrollment}/finish', [EnrollmentController::class, 'finish']);

        Route::apiResource('progress', ProgressController::class);
        Route::post('/progress/{progress}/complete', [ProgressController::class, 'complete']);

        Route::apiResource('quiz-results', QuizResultController::class);
        Route::post('/quiz-results/{quizResult}/submit', [QuizResultController::class, 'submit']);

        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        Route::get('/payments/{payment}/status', [PaymentController::class, 'checkStatus']);

        Route::post('/courses/{course:slug}/payment', [PaymentController::class, 'createCoursePayment']);
        Route::post('/bootcamps/{bootcamp}/payment', [PaymentController::class, 'createBootcampPayment']);

        Route::get('/instructor-management', [InstructorManagementController::class, 'index']);
        Route::post('/instructor-management', [InstructorManagementController::class, 'store']);
        Route::get('/instructor-management/{instructor}', [InstructorManagementController::class, 'show']);
        Route::put('/instructor-management/{instructor}', [InstructorManagementController::class, 'update']);
        Route::post('/instructor-management/{instructor}/reset-password', [InstructorManagementController::class, 'resetPassword']);
        Route::delete('/instructor-management/{instructor}', [InstructorManagementController::class, 'destroy']);

        Route::get('/student-management', [StudentManagementController::class, 'index']);
        Route::get('/student-management/{student}', [StudentManagementController::class, 'show']);
        Route::put('/student-management/{student}', [StudentManagementController::class, 'update']);
        Route::post('/student-management/{student}/reset-password', [StudentManagementController::class, 'resetPassword']);
        Route::delete('/student-management/{student}', [StudentManagementController::class, 'destroy']);
    });
});
