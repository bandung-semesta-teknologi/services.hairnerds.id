<?php

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

describe('question crud api', function () {
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
            'course_id' => $this->publishedCourse->id
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

    describe('guest access (forbidden)', function () {
        it('guest user cannot view questions', function () {
            getJson('/api/academy/questions')
                ->assertUnauthorized();
        });

        it('guest user cannot view single question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot create question', function () {
            postJson('/api/academy/questions', [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'What is the most important barbering tool?',
                'score' => 10
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            putJson("/api/academy/questions/{$question->id}", [
                'question' => 'Updated question text'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            deleteJson("/api/academy/questions/{$question->id}")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all questions from all courses', function () {
            Question::factory()->count(3)->create(['quiz_id' => $this->publishedQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $this->draftQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $this->otherQuiz->id]);

            getJson('/api/academy/questions')
                ->assertOk()
                ->assertJsonCount(7, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'quiz_id',
                            'quiz',
                            'type',
                            'question',
                            'score',
                            'answer_banks',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can create single choice question', function () {
            $questionData = [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'What is the most important barbering tool?',
                'score' => 10
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Question created successfully')
                ->assertJsonPath('data.type', 'single_choice')
                ->assertJsonPath('data.question', 'What is the most important barbering tool?')
                ->assertJsonPath('data.score', 10);

            $this->assertDatabaseHas('questions', [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'What is the most important barbering tool?'
            ]);
        });

        it('admin can create question with answers in bulk', function () {
            $questionData = [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'multiple_choice',
                'question' => 'Which are basic barbering tools?',
                'score' => 15,
                'answers' => [
                    ['answer' => 'Scissors', 'is_true' => true],
                    ['answer' => 'Comb', 'is_true' => true],
                    ['answer' => 'Phone', 'is_true' => false],
                    ['answer' => 'Computer', 'is_true' => false]
                ]
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('data.type', 'multiple_choice')
                ->assertJsonPath('data.question', 'Which are basic barbering tools?')
                ->assertJsonPath('data.score', 15)
                ->assertJsonCount(4, 'data.answer_banks');

            $this->assertDatabaseHas('questions', [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'multiple_choice',
                'question' => 'Which are basic barbering tools?'
            ]);

            $this->assertDatabaseHas('answer_banks', [
                'answer' => 'Scissors',
                'is_true' => true
            ]);

            $this->assertDatabaseHas('answer_banks', [
                'answer' => 'Phone',
                'is_true' => false
            ]);
        });

        it('admin can update any question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            $updateData = [
                'type' => 'multiple_choice',
                'question' => 'Updated question text',
                'score' => 20
            ];

            putJson("/api/academy/questions/{$question->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Question updated successfully')
                ->assertJsonPath('data.type', 'multiple_choice')
                ->assertJsonPath('data.question', 'Updated question text')
                ->assertJsonPath('data.score', 20);

            $this->assertDatabaseHas('questions', [
                'id' => $question->id,
                'type' => 'multiple_choice',
                'question' => 'Updated question text',
                'score' => 20
            ]);
        });

        it('admin can delete any question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            deleteJson("/api/academy/questions/{$question->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Question deleted successfully');

            $this->assertSoftDeleted('questions', ['id' => $question->id]);
        });

        it('admin can view any question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->draftQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $question->id)
                ->assertJsonStructure([
                    'data' => [
                        'quiz',
                        'answer_banks'
                    ]
                ]);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see questions only from their own courses', function () {
            Question::factory()->count(3)->create(['quiz_id' => $this->publishedQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $this->draftQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $this->otherQuiz->id]);

            getJson('/api/academy/questions')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can create question for their own course', function () {
            $questionData = [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'What is the proper angle for cutting?',
                'score' => 10
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Question created successfully')
                ->assertJsonPath('data.type', 'single_choice')
                ->assertJsonPath('data.question', 'What is the proper angle for cutting?')
                ->assertJsonPath('data.score', 10);
        });

        it('instructor cannot create question for other instructor course', function () {
            $questionData = [
                'quiz_id' => $this->otherQuiz->id,
                'type' => 'single_choice',
                'question' => 'Unauthorized question',
                'score' => 10
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertStatus(403)
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Unauthorized to create questions for this quiz');
        });

        it('instructor can update question from their own course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            putJson("/api/academy/questions/{$question->id}", [
                'question' => 'Instructor Updated Question',
                'score' => 15
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Question updated successfully')
                ->assertJsonPath('data.question', 'Instructor Updated Question');
        });

        it('instructor can delete question from their own course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            deleteJson("/api/academy/questions/{$question->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Question deleted successfully');

            $this->assertSoftDeleted('questions', ['id' => $question->id]);
        });

        it('instructor can view question from their own course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->draftQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $question->id);
        });

        it('instructor cannot update question from other instructor course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->otherQuiz->id]);

            putJson("/api/academy/questions/{$question->id}", [
                'question' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete question from other instructor course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->otherQuiz->id]);

            deleteJson("/api/academy/questions/{$question->id}")
                ->assertForbidden();
        });

        it('instructor cannot view question from other instructor course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->otherQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view questions from enrolled published courses only', function () {
            Question::factory()->count(3)->create(['quiz_id' => $this->publishedQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $this->draftQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $this->otherQuiz->id]);

            getJson('/api/academy/questions')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view single question from enrolled published course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $question->id);
        });

        it('student cannot view question from draft course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->draftQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertForbidden();
        });

        it('student cannot view question from unenrolled course', function () {
            $question = Question::factory()->create(['quiz_id' => $this->otherQuiz->id]);

            getJson("/api/academy/questions/{$question->id}")
                ->assertForbidden();
        });

        it('student cannot create question', function () {
            postJson('/api/academy/questions', [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'Student Question',
                'score' => 5
            ])
                ->assertForbidden();
        });

        it('student cannot update question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            putJson("/api/academy/questions/{$question->id}", [
                'question' => 'Student Updated Question'
            ])
                ->assertForbidden();
        });

        it('student cannot delete question', function () {
            $question = Question::factory()->create(['quiz_id' => $this->publishedQuiz->id]);

            deleteJson("/api/academy/questions/{$question->id}")
                ->assertForbidden();
        });
    });

    describe('filtering and searching', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('can filter questions by quiz', function () {
            $quiz2 = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            Question::factory()->count(3)->create(['quiz_id' => $this->publishedQuiz->id]);
            Question::factory()->count(2)->create(['quiz_id' => $quiz2->id]);

            getJson("/api/academy/questions?quiz_id={$this->publishedQuiz->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('can filter questions by type', function () {
            Question::factory()->count(2)->create([
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice'
            ]);
            Question::factory()->count(3)->create([
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'multiple_choice'
            ]);
            Question::factory()->create([
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'fill_blank'
            ]);

            getJson('/api/academy/questions?type=single_choice')
                ->assertOk()
                ->assertJsonCount(2, 'data');

            getJson('/api/academy/questions?type=multiple_choice')
                ->assertOk()
                ->assertJsonCount(3, 'data');

            getJson('/api/academy/questions?type=fill_blank')
                ->assertOk()
                ->assertJsonCount(1, 'data');
        });

        it('can search questions by question text', function () {
            Question::factory()->create([
                'quiz_id' => $this->publishedQuiz->id,
                'question' => 'What is the best programming language?'
            ]);
            Question::factory()->create([
                'quiz_id' => $this->publishedQuiz->id,
                'question' => 'How to create a database?'
            ]);

            getJson('/api/academy/questions?search=programming')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.question', 'What is the best programming language?');
        });

        it('can paginate questions', function () {
            Question::factory()->count(25)->create(['quiz_id' => $this->publishedQuiz->id]);

            getJson('/api/academy/questions?per_page=10')
                ->assertOk()
                ->assertJsonCount(10, 'data')
                ->assertJsonStructure([
                    'data',
                    'links',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ]
                ]);
        });
    });

    describe('validation tests', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('validates required fields when creating question', function () {
            postJson('/api/academy/questions', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['quiz_id', 'type', 'question']);
        });

        it('validates question type enum values', function () {
            $questionData = [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'invalid_type',
                'question' => 'Test question'
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['type']);
        });

        it('validates quiz_id exists', function () {
            $questionData = [
                'quiz_id' => 99999,
                'type' => 'single_choice',
                'question' => 'Test question'
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['quiz_id']);
        });

        it('validates answers array structure when provided', function () {
            $questionData = [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'Test question',
                'answers' => [
                    ['answer' => 'Valid answer'],
                    ['is_true' => true]
                ]
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['answers.0.is_true', 'answers.1.answer']);
        });

        it('validates score is not negative', function () {
            $questionData = [
                'quiz_id' => $this->publishedQuiz->id,
                'type' => 'single_choice',
                'question' => 'Test question',
                'score' => -5
            ];

            postJson('/api/academy/questions', $questionData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['score']);
        });
    });

    describe('error handling', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('returns 404 when question not found', function () {
            getJson('/api/academy/questions/99999')
                ->assertNotFound();
        });

        it('returns 404 when updating non-existent question', function () {
            putJson('/api/academy/questions/99999', [
                'question' => 'Updated question'
            ])
                ->assertNotFound();
        });

        it('returns 404 when deleting non-existent question', function () {
            deleteJson('/api/academy/questions/99999')
                ->assertNotFound();
        });
    });
});
