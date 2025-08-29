<?php

use App\Jobs\AutoSubmitQuiz;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('quiz result crud api', function () {
    beforeEach(function () {
        $this->admin = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'admin']);

        $this->instructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->otherInstructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->otherStudent = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->categories = Category::factory()->count(2)->create();

        $this->publishedCourse = Course::factory()->published()->create();
        $this->publishedCourse->categories()->attach($this->categories->first()->id);
        $this->publishedCourse->instructors()->attach($this->instructor->id);

        $this->draftCourse = Course::factory()->draft()->create();
        $this->draftCourse->categories()->attach($this->categories->last()->id);
        $this->draftCourse->instructors()->attach($this->instructor->id);

        $this->otherCourse = Course::factory()->published()->create();
        $this->otherCourse->categories()->attach($this->categories->first()->id);
        $this->otherCourse->instructors()->attach($this->otherInstructor->id);

        $this->publishedSection = Section::factory()->create(['course_id' => $this->publishedCourse->id]);
        $this->draftSection = Section::factory()->create(['course_id' => $this->draftCourse->id]);
        $this->otherSection = Section::factory()->create(['course_id' => $this->otherCourse->id]);

        $this->publishedLesson = Lesson::factory()->create([
            'section_id' => $this->publishedSection->id,
            'course_id' => $this->publishedCourse->id
        ]);
        $this->draftLesson = Lesson::factory()->create([
            'section_id' => $this->draftSection->id,
            'course_id' => $this->draftCourse->id
        ]);
        $this->otherLesson = Lesson::factory()->create([
            'section_id' => $this->otherSection->id,
            'course_id' => $this->otherCourse->id
        ]);

        $this->publishedQuiz = Quiz::factory()->create([
            'section_id' => $this->publishedSection->id,
            'lesson_id' => $this->publishedLesson->id,
            'course_id' => $this->publishedCourse->id,
            'duration' => '01:00:00'
        ]);
        $this->draftQuiz = Quiz::factory()->create([
            'section_id' => $this->draftSection->id,
            'lesson_id' => $this->draftLesson->id,
            'course_id' => $this->draftCourse->id
        ]);
        $this->otherQuiz = Quiz::factory()->create([
            'section_id' => $this->otherSection->id,
            'lesson_id' => $this->otherLesson->id,
            'course_id' => $this->otherCourse->id
        ]);

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view only their own quiz results', function () {
            QuizResult::factory()->count(3)->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);
            QuizResult::factory()->count(2)->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            getJson('/api/quiz-results')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view their own quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            getJson("/api/quiz-results/{$quizResult->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $quizResult->id);
        });

        it('student cannot view other students quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            getJson("/api/quiz-results/{$quizResult->id}")
                ->assertForbidden();
        });

        it('student can create quiz result for themselves when enrolled in published course', function () {
            Queue::fake();

            $quizResultData = [
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'started_at' => now()
            ];

            postJson('/api/quiz-results', $quizResultData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz result created successfully')
                ->assertJsonPath('data.user_id', $this->student->id);

            Queue::assertPushed(AutoSubmitQuiz::class);
        });

        it('student cannot create quiz result for unenrolled course', function () {
            $quizResultData = [
                'quiz_id' => $this->otherQuiz->id,
                'lesson_id' => $this->otherLesson->id
            ];

            postJson('/api/quiz-results', $quizResultData)
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Must be enrolled in course to take quiz');
        });

        it('student cannot create active quiz if they already have one', function () {
            QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            $quizResultData = [
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ];

            postJson('/api/quiz-results', $quizResultData)
                ->assertUnprocessable()
                ->assertJsonPath('message', 'You already have an active quiz attempt');
        });

        it('student can update their own unsubmitted quiz result', function () {
            $futureQuiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'duration' => '02:00:00'
            ]);

            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $futureQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false,
                'started_at' => now()
            ]);

            putJson("/api/quiz-results/{$quizResult->id}", [
                'answered' => 5,
                'correct_answers' => 4,
                'total_obtained_marks' => 40
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('data.answered', 5);
        });

        it('student cannot update their own submitted quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => true
            ]);

            putJson("/api/quiz-results/{$quizResult->id}", [
                'answered' => 8
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Quiz already submitted');
        });

        it('student cannot update other students quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            putJson("/api/quiz-results/{$quizResult->id}", [
                'answered' => 3
            ])
                ->assertForbidden();
        });

        it('student cannot delete quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            deleteJson("/api/quiz-results/{$quizResult->id}")
                ->assertForbidden();
        });

        it('student can submit their own unsubmitted quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            $this->enrollment->refresh();
            $initialAttempts = $this->enrollment->quiz_attempts;

            postJson("/api/quiz-results/{$quizResult->id}/submit")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz submitted successfully')
                ->assertJsonPath('data.is_submitted', true);

            $this->enrollment->refresh();
            expect($this->enrollment->quiz_attempts)->toBe($initialAttempts + 1);
        });

        it('student cannot submit other students quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            postJson("/api/quiz-results/{$quizResult->id}/submit")
                ->assertForbidden();
        });

        it('student gets error when updating expired quiz', function () {
            $expiredQuiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'duration' => '00:01:00'
            ]);

            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $expiredQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false,
                'started_at' => now()->subHours(2)
            ]);

            putJson("/api/quiz-results/{$quizResult->id}", [
                'answered' => 5,
                'correct_answers' => 3,
                'total_obtained_marks' => 30
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Quiz time has expired and has been automatically submitted');

            $this->assertDatabaseHas('quiz_results', [
                'id' => $quizResult->id,
                'is_submitted' => true
            ]);
        });

        it('student manual submit increments quiz attempts', function () {
            $longQuiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'duration' => '03:00:00'
            ]);

            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $longQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false,
                'started_at' => now()
            ]);

            $this->enrollment->refresh();
            $initialAttempts = $this->enrollment->quiz_attempts;

            putJson("/api/quiz-results/{$quizResult->id}", [
                'answered' => 10,
                'correct_answers' => 8,
                'total_obtained_marks' => 80,
                'is_submitted' => true
            ])
                ->assertOk()
                ->assertJsonPath('data.is_submitted', true);

            $this->enrollment->refresh();
            expect($this->enrollment->quiz_attempts)->toBe($initialAttempts + 1);
        });
    });

    describe('quiz expiry helper methods', function () {
        it('quiz result can check if expired', function () {
            $expiredQuiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'duration' => '00:30:00'
            ]);

            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $expiredQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'started_at' => now()->subHours(1)
            ]);

            expect($quizResult->isExpired())->toBeTrue();
        });

        it('quiz result can get expected finish time', function () {
            $startTime = now();

            $longQuiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'duration' => '01:00:00'
            ]);

            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $longQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'started_at' => $startTime
            ]);

            $expectedFinish = $quizResult->getExpectedFinishedAt();

            expect($expectedFinish)->not->toBeNull();
            expect($expectedFinish->gt($startTime))->toBeTrue();
            $diff = $startTime->diffInMinutes($expectedFinish);
            expect($diff >= 59.9 && $diff <= 60.1)->toBeTrue();
        });
    });

    describe('guest access (forbidden)', function () {
        it('guest user cannot view quiz results', function () {
            getJson('/api/quiz-results')
                ->assertUnauthorized();
        });

        it('guest user cannot create quiz result', function () {
            postJson('/api/quiz-results', [
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot submit quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            postJson("/api/quiz-results/{$quizResult->id}/submit")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all quiz results from all courses', function () {
            QuizResult::factory()->count(3)->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);
            QuizResult::factory()->count(2)->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->otherQuiz->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson('/api/quiz-results')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('admin can create new quiz result for any user', function () {
            $quizResultData = [
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'answered' => 10,
                'correct_answers' => 8,
                'total_obtained_marks' => 80,
                'started_at' => now()
            ];

            postJson('/api/quiz-results', $quizResultData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz result created successfully')
                ->assertJsonPath('data.answered', 10)
                ->assertJsonPath('data.correct_answers', 8)
                ->assertJsonPath('data.total_obtained_marks', 80);

            $this->assertDatabaseHas('quiz_results', [
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'answered' => 10,
                'correct_answers' => 8
            ]);
        });

        it('validates required fields when admin creating quiz result', function () {
            postJson('/api/quiz-results', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['user_id', 'quiz_id', 'lesson_id']);
        });

        it('admin can update any quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            $updateData = [
                'answered' => 15,
                'correct_answers' => 12,
                'total_obtained_marks' => 120,
                'is_submitted' => true,
                'finished_at' => now()
            ];

            putJson("/api/quiz-results/{$quizResult->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz result updated successfully')
                ->assertJsonPath('data.answered', 15)
                ->assertJsonPath('data.correct_answers', 12)
                ->assertJsonPath('data.total_obtained_marks', 120);
        });

        it('admin can delete any quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            deleteJson("/api/quiz-results/{$quizResult->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz result deleted successfully');

            $this->assertSoftDeleted('quiz_results', ['id' => $quizResult->id]);
        });

        it('admin can view any quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->otherQuiz->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson("/api/quiz-results/{$quizResult->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $quizResult->id);
        });

        it('admin can submit any quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            postJson("/api/quiz-results/{$quizResult->id}/submit")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz submitted successfully')
                ->assertJsonPath('data.is_submitted', true);

            $this->assertDatabaseHas('quiz_results', [
                'id' => $quizResult->id,
                'is_submitted' => true
            ]);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see quiz results only from their own courses', function () {
            QuizResult::factory()->count(3)->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);
            QuizResult::factory()->count(2)->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->otherQuiz->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson('/api/quiz-results')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('instructor can create quiz result for students in their course', function () {
            $quizResultData = [
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'answered' => 8,
                'correct_answers' => 6,
                'total_obtained_marks' => 60
            ];

            postJson('/api/quiz-results', $quizResultData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz result created successfully');
        });

        it('instructor cannot create quiz result for other instructors courses', function () {
            $quizResultData = [
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->otherQuiz->id,
                'lesson_id' => $this->otherLesson->id
            ];

            postJson('/api/quiz-results', $quizResultData)
                ->assertForbidden();
        });

        it('instructor can update quiz result from their own course', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            $response = putJson("/api/quiz-results/{$quizResult->id}", [
                'answered' => 12,
                'correct_answers' => 10,
                'total_obtained_marks' => 100
            ]);

            if ($response->status() !== 200) {
                dd($response->json());
            }

            $response->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can delete quiz result from their own course', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            deleteJson("/api/quiz-results/{$quizResult->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can view quiz result from their own course', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id
            ]);

            getJson("/api/quiz-results/{$quizResult->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $quizResult->id);
        });

        it('instructor cannot view quiz result from other instructor course', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->otherQuiz->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson("/api/quiz-results/{$quizResult->id}")
                ->assertForbidden();
        });

        it('instructor can submit quiz result from their own course', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false
            ]);

            postJson("/api/quiz-results/{$quizResult->id}/submit")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('data.is_submitted', true);
        });
    });

    describe('auto submit job functionality', function () {
        it('auto submit job handles quiz result correctly', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => false,
                'started_at' => now()->subHours(2)
            ]);

            $job = new AutoSubmitQuiz($quizResult->id);
            $job->handle();

            $this->assertDatabaseHas('quiz_results', [
                'id' => $quizResult->id,
                'is_submitted' => true
            ]);

            $this->enrollment->refresh();
            expect($this->enrollment->quiz_attempts)->toBeGreaterThan(0);
        });

        it('auto submit job skips already submitted quiz', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->publishedQuiz->id,
                'lesson_id' => $this->publishedLesson->id,
                'is_submitted' => true,
                'started_at' => now()->subHours(2)
            ]);

            $job = new AutoSubmitQuiz($quizResult->id);
            $job->handle();

            $this->assertDatabaseHas('quiz_results', [
                'id' => $quizResult->id,
                'is_submitted' => true
            ]);
        });

        it('auto submit job handles non-existent quiz result', function () {
            $job = new AutoSubmitQuiz(999999);

            expect(fn() => $job->handle())->not->toThrow(\Exception::class);
        });
    });
});
