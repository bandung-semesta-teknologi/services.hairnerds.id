<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('quiz crud api', function () {
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

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('guest access (forbidden)', function () {
        it('guest user cannot view quizzes', function () {
            getJson('/api/quizzes')
                ->assertUnauthorized();
        });

        it('guest user cannot view single quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot create quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Basic Knowledge Test',
                'instruction' => 'Answer all questions',
                'duration' => '00:30:00'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/quizzes/{$quiz->id}", [
                'title' => 'Updated title'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/quizzes/{$quiz->id}")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all quizzes from all courses', function () {
            Quiz::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/quizzes')
                ->assertOk()
                ->assertJsonCount(7, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'section_id',
                            'section',
                            'lesson_id',
                            'lesson',
                            'course_id',
                            'course',
                            'title',
                            'instruction',
                            'duration',
                            'total_marks',
                            'pass_marks',
                            'max_retakes',
                            'min_lesson_taken',
                            'questions',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can create new quiz', function () {
            $quizData = [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Laravel Fundamentals Quiz',
                'instruction' => 'Answer all questions to complete the quiz',
                'duration' => '01:00:00',
                'total_marks' => 100,
                'pass_marks' => 70,
                'max_retakes' => 3,
                'min_lesson_taken' => 5
            ];

            postJson('/api/quizzes', $quizData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz created successfully')
                ->assertJsonPath('data.title', 'Laravel Fundamentals Quiz')
                ->assertJsonPath('data.total_marks', 100)
                ->assertJsonPath('data.pass_marks', 70);

            $this->assertDatabaseHas('quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Laravel Fundamentals Quiz',
                'total_marks' => 100
            ]);
        });

        it('validates required fields when creating quiz', function () {
            postJson('/api/quizzes', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['section_id', 'lesson_id', 'course_id', 'title']);
        });

        it('admin can update any quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            $updateData = [
                'title' => 'Updated Quiz Title',
                'instruction' => 'Updated instructions',
                'total_marks' => 150,
                'pass_marks' => 90
            ];

            putJson("/api/quizzes/{$quiz->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz updated successfully')
                ->assertJsonPath('data.title', 'Updated Quiz Title')
                ->assertJsonPath('data.instruction', 'Updated instructions')
                ->assertJsonPath('data.total_marks', 150);

            $this->assertDatabaseHas('quizzes', [
                'id' => $quiz->id,
                'title' => 'Updated Quiz Title',
                'instruction' => 'Updated instructions',
                'total_marks' => 150
            ]);
        });

        it('admin can delete any quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/quizzes/{$quiz->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz deleted successfully');

            $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
        });

        it('admin can view any quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $quiz->id);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see quizzes only from their own courses', function () {
            Quiz::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/quizzes')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can create quiz for their own course', function () {
            $quizData = [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'PHP Basics Quiz',
                'instruction' => 'Test your PHP knowledge',
                'duration' => '00:45:00',
                'total_marks' => 80,
                'pass_marks' => 60
            ];

            postJson('/api/quizzes', $quizData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz created successfully')
                ->assertJsonPath('data.title', 'PHP Basics Quiz')
                ->assertJsonPath('data.total_marks', 80);
        });

        it('instructor can update quiz from their own course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/quizzes/{$quiz->id}", [
                'title' => 'Instructor Updated Quiz',
                'instruction' => 'Updated by instructor',
                'total_marks' => 120
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz updated successfully')
                ->assertJsonPath('data.title', 'Instructor Updated Quiz');
        });

        it('instructor can delete quiz from their own course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/quizzes/{$quiz->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz deleted successfully');

            $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
        });

        it('instructor can view quiz from their own course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $quiz->id);
        });

        it('instructor cannot update quiz from other instructor course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            putJson("/api/quizzes/{$quiz->id}", [
                'title' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete quiz from other instructor course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            deleteJson("/api/quizzes/{$quiz->id}")
                ->assertForbidden();
        });

        it('instructor cannot view quiz from other instructor course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view quizzes from enrolled published courses only', function () {
            Quiz::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/quizzes')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view single quiz from enrolled published course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $quiz->id);
        });

        it('student cannot view quiz from draft course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertForbidden();
        });

        it('student cannot view quiz from unenrolled course', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/quizzes/{$quiz->id}")
                ->assertForbidden();
        });

        it('student cannot create quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Student Quiz',
                'instruction' => 'Test quiz'
            ])
                ->assertForbidden();
        });

        it('student cannot update quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/quizzes/{$quiz->id}", [
                'title' => 'Student Updated Quiz'
            ])
                ->assertForbidden();
        });

        it('student cannot delete quiz', function () {
            $quiz = Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/quizzes/{$quiz->id}")
                ->assertForbidden();
        });
    });

    describe('filtering and searching', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('can filter quizzes by section_id', function () {
            Quiz::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/quizzes?section_id={$this->publishedSection->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('can filter quizzes by lesson_id', function () {
            Quiz::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'lesson_id' => $this->draftLesson->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/quizzes?lesson_id={$this->publishedLesson->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('can filter quizzes by course_id', function () {
            Quiz::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Quiz::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'lesson_id' => $this->otherLesson->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/quizzes?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('can search quizzes by title', function () {
            Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Laravel Basics Quiz'
            ]);
            Quiz::factory()->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'PHP Advanced Quiz'
            ]);

            getJson('/api/quizzes?search=Laravel')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.title', 'Laravel Basics Quiz');
        });

        it('can paginate quizzes', function () {
            Quiz::factory()->count(25)->create([
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson('/api/quizzes?per_page=10')
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

    describe('validation errors', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('validates section_id exists when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => 99999,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['section_id']);
        });

        it('validates lesson_id exists when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => 99999,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['lesson_id']);
        });

        it('validates course_id exists when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => 99999,
                'title' => 'Test Quiz'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['course_id']);
        });

        it('validates title is required when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });

        it('validates title is string when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 123
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });

        it('validates duration format when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz',
                'duration' => 'invalid-duration'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['duration']);
        });

        it('validates total_marks is numeric when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz',
                'total_marks' => 'not-a-number'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['total_marks']);
        });

        it('validates pass_marks is numeric when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz',
                'pass_marks' => 'not-a-number'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['pass_marks']);
        });

        it('validates max_retakes is numeric when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz',
                'max_retakes' => 'not-a-number'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['max_retakes']);
        });

        it('validates min_lesson_taken is numeric when creating quiz', function () {
            postJson('/api/quizzes', [
                'section_id' => $this->publishedSection->id,
                'lesson_id' => $this->publishedLesson->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Test Quiz',
                'min_lesson_taken' => 'not-a-number'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['min_lesson_taken']);
        });
    });

    describe('error handling', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('returns 404 for non-existent quiz', function () {
            getJson('/api/quizzes/99999')
                ->assertNotFound();
        });

        it('returns 404 when updating non-existent quiz', function () {
            putJson('/api/quizzes/99999', [
                'title' => 'Updated Title'
            ])
                ->assertNotFound();
        });

        it('returns 404 when deleting non-existent quiz', function () {
            deleteJson('/api/quizzes/99999')
                ->assertNotFound();
        });
    });
});
