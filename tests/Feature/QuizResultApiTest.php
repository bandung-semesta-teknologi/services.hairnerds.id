<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('quiz result crud api', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->published()->verified()->create();
        $this->course->categories()->attach($this->categories->first()->id);

        $this->section = Section::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'type' => 'quiz'
        ]);

        $this->quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'total_marks' => 100,
            'pass_marks' => 60
        ]);

        $this->student = User::factory()->create(['role' => 'student']);
    });

    it('user can get all quiz results with pagination', function () {
        QuizResult::factory()->count(8)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson('/api/quiz-results')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user',
                        'quiz_id',
                        'quiz',
                        'lesson_id',
                        'lesson',
                        'answered',
                        'correct_answers',
                        'total_obtained_marks',
                        'is_submitted',
                        'started_at',
                        'finished_at',
                        'duration_minutes',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('user can filter quiz results by user', function () {
        $user2 = User::factory()->create(['role' => 'student']);

        QuizResult::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);
        QuizResult::factory()->count(2)->create([
            'user_id' => $user2->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson("/api/quiz-results?user_id={$this->student->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter quiz results by quiz', function () {
        $quiz2 = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        QuizResult::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);
        QuizResult::factory()->count(2)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $quiz2->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson("/api/quiz-results?quiz_id={$this->quiz->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter quiz results by lesson', function () {
        $lesson2 = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'type' => 'quiz'
        ]);

        QuizResult::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);
        QuizResult::factory()->count(2)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $lesson2->id
        ]);

        getJson("/api/quiz-results?lesson_id={$this->lesson->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter quiz results by status', function () {
        QuizResult::factory()->submitted()->count(2)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);
        QuizResult::factory()->inProgress()->count(3)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson('/api/quiz-results?status=submitted')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        getJson('/api/quiz-results?status=in_progress')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('quiz results are ordered by started_at desc', function () {
        $older = QuizResult::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'started_at' => now()->subHours(2)
        ]);

        $newer = QuizResult::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'started_at' => now()
        ]);

        getJson('/api/quiz-results')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('user can create new quiz result', function () {
        $quizResultData = [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'answered' => 0,
            'correct_answers' => 0,
            'total_obtained_marks' => 0,
            'is_submitted' => false,
            'started_at' => now()->toDateTimeString()
        ];

        postJson('/api/quiz-results', $quizResultData)
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->student->id)
            ->assertJsonPath('data.quiz_id', $this->quiz->id)
            ->assertJsonPath('data.lesson_id', $this->lesson->id)
            ->assertJsonPath('data.is_submitted', false);

        $this->assertDatabaseHas('quiz_results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'is_submitted' => false
        ]);
    });

    it('validates required fields when creating quiz result', function () {
        postJson('/api/quiz-results', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'quiz_id', 'lesson_id']);
    });

    it('validates foreign key relationships when creating quiz result', function () {
        $quizResultData = [
            'user_id' => 99999,
            'quiz_id' => 99999,
            'lesson_id' => 99999
        ];

        postJson('/api/quiz-results', $quizResultData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'quiz_id', 'lesson_id']);
    });

    it('user can get single quiz result with relationships', function () {
        $quizResult = QuizResult::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson("/api/quiz-results/{$quizResult->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $quizResult->id)
            ->assertJsonPath('data.user.name', $this->student->name)
            ->assertJsonPath('data.quiz.id', $this->quiz->id)
            ->assertJsonPath('data.lesson.id', $this->lesson->id);
    });

    it('returns 404 when quiz result not found', function () {
        getJson('/api/quiz-results/99999')
            ->assertNotFound();
    });

    it('user can update quiz result', function () {
        $quizResult = QuizResult::factory()->inProgress()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'answered' => 5,
            'correct_answers' => 3
        ]);

        $updateData = [
            'answered' => 10,
            'correct_answers' => 8,
            'total_obtained_marks' => 80
        ];

        putJson("/api/quiz-results/{$quizResult->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.answered', 10)
            ->assertJsonPath('data.correct_answers', 8)
            ->assertJsonPath('data.total_obtained_marks', 80);

        $this->assertDatabaseHas('quiz_results', [
            'id' => $quizResult->id,
            'answered' => 10,
            'correct_answers' => 8,
            'total_obtained_marks' => 80
        ]);
    });

    it('user can partially update quiz result', function () {
        $quizResult = QuizResult::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'answered' => 5
        ]);

        putJson("/api/quiz-results/{$quizResult->id}", ['answered' => 8])
            ->assertOk()
            ->assertJsonPath('data.answered', 8);
    });

    it('user can delete quiz result', function () {
        $quizResult = QuizResult::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        deleteJson("/api/quiz-results/{$quizResult->id}")
            ->assertOk()
            ->assertJson(['message' => 'Quiz result deleted successfully']);

        $this->assertSoftDeleted('quiz_results', ['id' => $quizResult->id]);
    });

    it('returns 404 when deleting non-existent quiz result', function () {
        deleteJson('/api/quiz-results/99999')
            ->assertNotFound();
    });

    it('user can submit quiz result', function () {
        $quizResult = QuizResult::factory()->inProgress()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'answered' => 10,
            'correct_answers' => 8,
            'total_obtained_marks' => 80
        ]);

        postJson("/api/quiz-results/{$quizResult->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.is_submitted', true)
            ->assertJsonPath('message', 'Quiz submitted successfully');

        $this->assertDatabaseHas('quiz_results', [
            'id' => $quizResult->id,
            'is_submitted' => true
        ]);

        $quizResult->refresh();
        expect($quizResult->finished_at)->not()->toBeNull();
    });

    it('calculates pass status correctly for submitted quiz', function () {
        $passingQuizResult = QuizResult::factory()->submitted()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'total_obtained_marks' => 80
        ]);

        $failingQuizResult = QuizResult::factory()->submitted()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'total_obtained_marks' => 40
        ]);

        getJson("/api/quiz-results/{$passingQuizResult->id}")
            ->assertOk()
            ->assertJsonPath('data.pass_status', 'passed');

        getJson("/api/quiz-results/{$failingQuizResult->id}")
            ->assertOk()
            ->assertJsonPath('data.pass_status', 'failed');
    });

    it('calculates duration correctly for finished quiz', function () {
        $startedAt = now()->subMinutes(30);
        $finishedAt = now();

        $quizResult = QuizResult::factory()->submitted()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt
        ]);

        getJson("/api/quiz-results/{$quizResult->id}")
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 30);
    });

    it('does not show pass status for in progress quiz', function () {
        $quizResult = QuizResult::factory()->inProgress()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson("/api/quiz-results/{$quizResult->id}")
            ->assertOk()
            ->assertJsonMissing(['pass_status']);
    });

    it('user can set custom per_page for pagination', function () {
        QuizResult::factory()->count(10)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson('/api/quiz-results?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('validates answered is not negative', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'answered' => -1
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answered']);
    });

    it('validates correct_answers is not negative', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'correct_answers' => -1
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['correct_answers']);
    });

    it('validates total_obtained_marks is not negative', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'total_obtained_marks' => -10
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_obtained_marks']);
    });

    it('validates started_at date format', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'started_at' => 'invalid-date'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['started_at']);
    });

    it('validates finished_at date format', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'finished_at' => 'invalid-date'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['finished_at']);
    });

    it('validates is_submitted as boolean', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'is_submitted' => 'not_boolean'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_submitted']);
    });

    it('accepts boolean values as string for is_submitted', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'is_submitted' => '1'
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_submitted', true);
    });

    it('defaults numeric fields to 0 when not provided', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ])
            ->assertCreated()
            ->assertJsonPath('data.answered', 0)
            ->assertJsonPath('data.correct_answers', 0)
            ->assertJsonPath('data.total_obtained_marks', 0);
    });

    it('defaults is_submitted to false when not provided', function () {
        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_submitted', false);
    });

    it('can handle multiple quiz attempts for same user and quiz', function () {
        QuizResult::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ]);

        postJson('/api/quiz-results', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id
        ])
            ->assertCreated();

        $this->assertDatabaseCount('quiz_results', 4);
    });

    it('can track quiz progress over time', function () {
        $quizResult = QuizResult::factory()->inProgress()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'lesson_id' => $this->lesson->id,
            'answered' => 3,
            'correct_answers' => 2,
            'total_obtained_marks' => 20
        ]);

        putJson("/api/quiz-results/{$quizResult->id}", [
            'answered' => 5,
            'correct_answers' => 4,
            'total_obtained_marks' => 40
        ])
            ->assertOk();

        putJson("/api/quiz-results/{$quizResult->id}", [
            'answered' => 10,
            'correct_answers' => 8,
            'total_obtained_marks' => 80
        ])
            ->assertOk()
            ->assertJsonPath('data.answered', 10)
            ->assertJsonPath('data.correct_answers', 8)
            ->assertJsonPath('data.total_obtained_marks', 80);
    });
});
