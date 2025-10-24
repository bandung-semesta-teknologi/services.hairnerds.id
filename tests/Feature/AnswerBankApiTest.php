<?php

use App\Models\AnswerBank;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('answer bank crud api', function () {
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

        $this->categories = Category::factory()->count(2)->create();

        $this->publishedCourse = Course::factory()->published()->create();
        $this->publishedCourse->categories()->attach($this->categories->first()->id);
        $this->publishedCourse->instructors()->attach($this->instructor->id);

        $this->otherCourse = Course::factory()->published()->create();
        $this->otherCourse->categories()->attach($this->categories->last()->id);
        $this->otherCourse->instructors()->attach($this->otherInstructor->id);

        $this->publishedSection = Section::factory()->create(['course_id' => $this->publishedCourse->id]);
        $this->otherSection = Section::factory()->create(['course_id' => $this->otherCourse->id]);

        $this->publishedLesson = Lesson::factory()->create([
            'section_id' => $this->publishedSection->id,
            'course_id' => $this->publishedCourse->id
        ]);
        $this->otherLesson = Lesson::factory()->create([
            'section_id' => $this->otherSection->id,
            'course_id' => $this->otherCourse->id
        ]);

        $this->publishedQuiz = Quiz::factory()->create([
            'section_id' => $this->publishedSection->id,
            'lesson_id' => $this->publishedLesson->id,
            'course_id' => $this->publishedCourse->id
        ]);
        $this->otherQuiz = Quiz::factory()->create([
            'section_id' => $this->otherSection->id,
            'lesson_id' => $this->otherLesson->id,
            'course_id' => $this->otherCourse->id
        ]);

        $this->publishedQuestion = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);
        $this->otherQuestion = Question::factory()->create(['quiz_id' => $this->otherQuiz->id]);

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('guest access (forbidden)', function () {
        it('guest user cannot view answer banks', function () {
            getJson('/api/academy/answer-banks')
                ->assertUnauthorized();
        });

        it('guest user cannot create answer bank', function () {
            postJson('/api/academy/answer-banks', [
                'question_id' => $this->publishedQuestion->id,
                'answer' => 'Test answer',
                'is_true' => true
            ])
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all answer banks from all courses', function () {
            AnswerBank::factory()->count(3)->create(['question_id' => $this->publishedQuestion->id]);
            AnswerBank::factory()->count(2)->create(['question_id' => $this->otherQuestion->id]);

            getJson('/api/academy/answer-banks')
                ->assertOk()
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'question_id',
                            'question',
                            'answer',
                            'is_true',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can create answer bank for any question', function () {
            $answerData = [
                'question_id' => $this->publishedQuestion->id,
                'answer' => 'Admin created answer',
                'is_true' => true
            ];

            postJson('/api/academy/answer-banks', $answerData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Answer created successfully')
                ->assertJsonPath('data.answer', 'Admin created answer')
                ->assertJsonPath('data.is_true', true);

            $this->assertDatabaseHas('answer_banks', [
                'question_id' => $this->publishedQuestion->id,
                'answer' => 'Admin created answer',
                'is_true' => true
            ]);
        });

        it('admin can update any answer bank', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->publishedQuestion->id]);

            $updateData = [
                'answer' => 'Admin updated answer',
                'is_true' => false
            ];

            putJson("/api/academy/answer-banks/{$answerBank->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Answer updated successfully')
                ->assertJsonPath('data.answer', 'Admin updated answer')
                ->assertJsonPath('data.is_true', false);

            $this->assertDatabaseHas('answer_banks', [
                'id' => $answerBank->id,
                'answer' => 'Admin updated answer',
                'is_true' => false
            ]);
        });

        it('admin can delete any answer bank', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->publishedQuestion->id]);

            deleteJson("/api/academy/answer-banks/{$answerBank->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Answer deleted successfully');

            $this->assertSoftDeleted('answer_banks', ['id' => $answerBank->id]);
        });

        it('admin can view any answer bank', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->otherQuestion->id]);

            getJson("/api/academy/answer-banks/{$answerBank->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $answerBank->id);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see answer banks only from their own courses', function () {
            AnswerBank::factory()->count(3)->create(['question_id' => $this->publishedQuestion->id]);
            AnswerBank::factory()->count(2)->create(['question_id' => $this->otherQuestion->id]);

            getJson('/api/academy/answer-banks')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('instructor can create answer bank for their own course', function () {
            $answerData = [
                'question_id' => $this->publishedQuestion->id,
                'answer' => 'Instructor created answer',
                'is_true' => true
            ];

            postJson('/api/academy/answer-banks', $answerData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Answer created successfully')
                ->assertJsonPath('data.answer', 'Instructor created answer')
                ->assertJsonPath('data.is_true', true);
        });

        it('instructor can update answer bank from their own course', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->publishedQuestion->id]);

            putJson("/api/academy/answer-banks/{$answerBank->id}", [
                'answer' => 'Instructor updated answer',
                'is_true' => false
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Answer updated successfully')
                ->assertJsonPath('data.answer', 'Instructor updated answer');
        });

        it('instructor can delete answer bank from their own course', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->publishedQuestion->id]);

            deleteJson("/api/academy/answer-banks/{$answerBank->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Answer deleted successfully');

            $this->assertSoftDeleted('answer_banks', ['id' => $answerBank->id]);
        });

        it('instructor can view answer bank from their own course', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->publishedQuestion->id]);

            getJson("/api/academy/answer-banks/{$answerBank->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $answerBank->id);
        });

        it('instructor cannot update answer bank from other instructor course', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->otherQuestion->id]);

            putJson("/api/academy/answer-banks/{$answerBank->id}", [
                'answer' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete answer bank from other instructor course', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->otherQuestion->id]);

            deleteJson("/api/academy/answer-banks/{$answerBank->id}")
                ->assertForbidden();
        });

        it('instructor cannot view answer bank from other instructor course', function () {
            $answerBank = AnswerBank::factory()->create(['question_id' => $this->otherQuestion->id]);

            getJson("/api/academy/answer-banks/{$answerBank->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student cannot view answer banks', function () {
            AnswerBank::factory()->count(3)->create(['question_id' => $this->publishedQuestion->id]);

            getJson('/api/academy/answer-banks')
                ->assertForbidden();
        });

        it('student cannot create answer bank', function () {
            postJson('/api/academy/answer-banks', [
                'question_id' => $this->publishedQuestion->id,
                'answer' => 'Student answer',
                'is_true' => true
            ])
                ->assertForbidden();
        });
    });
});
