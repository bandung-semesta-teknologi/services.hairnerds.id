<?php

use App\Http\Controllers\Api\AnswerBankController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BootcampController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseFaqController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizResultController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SectionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.verify');

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/courses-faqs', [CourseFaqController::class, 'index']);
Route::get('/courses-faqs/{coursesFaq}', [CourseFaqController::class, 'show']);

Route::get('/sections', [SectionController::class, 'index']);
Route::get('/sections/{section}', [SectionController::class, 'show']);

Route::get('/bootcamps', [BootcampController::class, 'index']);
Route::get('/bootcamps/{bootcamp}', [BootcampController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateUser']);

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendEmail'])->name('verification.send');

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
    Route::post('/courses/{course}/verify', [CourseController::class, 'verify']);
    Route::post('/courses/{course}/reject', [CourseController::class, 'reject']);

    Route::post('/bootcamps', [BootcampController::class, 'store']);
    Route::put('/bootcamps/{bootcamp}', [BootcampController::class, 'update']);
    Route::delete('/bootcamps/{bootcamp}', [BootcampController::class, 'destroy']);
    Route::post('/bootcamps/{bootcamp}/verify', [BootcampController::class, 'verify']);
    Route::post('/bootcamps/{bootcamp}/reject', [BootcampController::class, 'reject']);

    Route::post('/courses-faqs', [CourseFaqController::class, 'store']);
    Route::put('/courses-faqs/{coursesFaq}', [CourseFaqController::class, 'update']);
    Route::delete('/courses-faqs/{coursesFaq}', [CourseFaqController::class, 'destroy']);

    Route::post('/sections', [SectionController::class, 'store']);
    Route::put('/sections/{section}', [SectionController::class, 'update']);
    Route::delete('/sections/{section}', [SectionController::class, 'destroy']);

    Route::apiResource('lessons', LessonController::class);
    Route::apiResource('quizzes', QuizController::class);
    Route::apiResource('questions', QuestionController::class);
    Route::apiResource('answer-banks', AnswerBankController::class);
    Route::apiResource('reviews', ReviewController::class);

    Route::apiResource('enrollments', EnrollmentController::class);
    Route::post('/enrollments/{enrollment}/finish', [EnrollmentController::class, 'finish']);

    Route::apiResource('progress', ProgressController::class);
    Route::post('/progress/{progress}/complete', [ProgressController::class, 'complete']);

    Route::apiResource('quiz-results', QuizResultController::class);
    Route::post('/quiz-results/{quizResult}/submit', [QuizResultController::class, 'submit']);
});
