<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('enrollment crud api', function () {
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

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('guest access (forbidden)', function () {
        it('guest user cannot view enrollments', function () {
            getJson('/api/enrollments')
                ->assertUnauthorized();
        });

        it('guest user cannot view single enrollment', function () {
            getJson("/api/enrollments/{$this->enrollment->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot create enrollment', function () {
            postJson('/api/enrollments', [
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update enrollment', function () {
            putJson("/api/enrollments/{$this->enrollment->id}", [
                'quiz_attempts' => 5
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete enrollment', function () {
            deleteJson("/api/enrollments/{$this->enrollment->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot finish enrollment', function () {
            postJson("/api/enrollments/{$this->enrollment->id}/finish")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all enrollments from all courses', function () {
            Enrollment::factory()->count(3)->create([
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Enrollment::factory()->count(2)->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/enrollments')
                ->assertOk()
                ->assertJsonCount(6, 'data');
        });

        it('admin can create enrollment for any user to any course', function () {
            $enrollmentData = [
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id,
                'quiz_attempts' => 0
            ];

            postJson('/api/enrollments', $enrollmentData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment created successfully')
                ->assertJsonPath('data.user_id', $this->otherStudent->id)
                ->assertJsonPath('data.course_id', $this->publishedCourse->id)
                ->assertJsonPath('data.quiz_attempts', 0);

            $this->assertDatabaseHas('enrollments', [
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id
            ]);
        });

        it('admin can update any enrollment', function () {
            $updateData = [
                'quiz_attempts' => 5,
                'finished_at' => now()->toDateTimeString()
            ];

            putJson("/api/enrollments/{$this->enrollment->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment updated successfully')
                ->assertJsonPath('data.quiz_attempts', 5);

            $this->assertDatabaseHas('enrollments', [
                'id' => $this->enrollment->id,
                'quiz_attempts' => 5
            ]);
        });

        it('admin can delete any enrollment', function () {
            deleteJson("/api/enrollments/{$this->enrollment->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment deleted successfully');

            $this->assertSoftDeleted('enrollments', ['id' => $this->enrollment->id]);
        });

        it('admin can finish any enrollment', function () {
            postJson("/api/enrollments/{$this->enrollment->id}/finish")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment finished successfully')
                ->assertJsonPath('data.is_finished', true);

            $this->assertDatabaseHas('enrollments', [
                'id' => $this->enrollment->id
            ]);

            expect($this->enrollment->fresh()->finished_at)->not->toBeNull();
        });

        it('admin can view any enrollment', function () {
            getJson("/api/enrollments/{$this->enrollment->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $this->enrollment->id)
                ->assertJsonStructure([
                    'data' => [
                        'user',
                        'course',
                        'progress'
                    ]
                ]);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see enrollments only from their own courses', function () {
            Enrollment::factory()->count(3)->create([
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Enrollment::factory()->count(2)->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/enrollments')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });

        it('instructor can create enrollment for their own course', function () {
            $enrollmentData = [
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id
            ];

            postJson('/api/enrollments', $enrollmentData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment created successfully')
                ->assertJsonPath('data.user_id', $this->otherStudent->id)
                ->assertJsonPath('data.course_id', $this->publishedCourse->id);
        });

        it('instructor cannot create enrollment for other instructor course', function () {
            $enrollmentData = [
                'user_id' => $this->student->id,
                'course_id' => $this->otherCourse->id
            ];

            postJson('/api/enrollments', $enrollmentData)
                ->assertStatus(403)
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Cannot enroll students in courses you do not teach');
        });

        it('instructor can update enrollment from their own course', function () {
            putJson("/api/enrollments/{$this->enrollment->id}", [
                'quiz_attempts' => 3
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment updated successfully')
                ->assertJsonPath('data.quiz_attempts', 3);
        });

        it('instructor can delete enrollment from their own course', function () {
            deleteJson("/api/enrollments/{$this->enrollment->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment deleted successfully');

            $this->assertSoftDeleted('enrollments', ['id' => $this->enrollment->id]);
        });

        it('instructor can finish enrollment from their own course', function () {
            postJson("/api/enrollments/{$this->enrollment->id}/finish")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment finished successfully')
                ->assertJsonPath('data.is_finished', true);
        });

        it('instructor can view enrollment from their own course', function () {
            getJson("/api/enrollments/{$this->enrollment->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $this->enrollment->id);
        });

        it('instructor cannot update enrollment from other instructor course', function () {
            $otherEnrollment = Enrollment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id
            ]);

            putJson("/api/enrollments/{$otherEnrollment->id}", [
                'quiz_attempts' => 5
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete enrollment from other instructor course', function () {
            $otherEnrollment = Enrollment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id
            ]);

            deleteJson("/api/enrollments/{$otherEnrollment->id}")
                ->assertForbidden();
        });

        it('instructor cannot view enrollment from other instructor course', function () {
            $otherEnrollment = Enrollment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/enrollments/{$otherEnrollment->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view their own enrollments only', function () {
            Enrollment::factory()->count(2)->create([
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Enrollment::factory()->count(3)->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson('/api/enrollments')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can self-enroll to published course', function () {
            $enrollmentData = [
                'course_id' => $this->otherCourse->id
            ];

            postJson('/api/enrollments', $enrollmentData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment created successfully')
                ->assertJsonPath('data.user_id', $this->student->id)
                ->assertJsonPath('data.course_id', $this->otherCourse->id);

            $this->assertDatabaseHas('enrollments', [
                'user_id' => $this->student->id,
                'course_id' => $this->otherCourse->id
            ]);
        });

        it('student cannot enroll to draft course', function () {
            $enrollmentData = [
                'course_id' => $this->draftCourse->id
            ];

            postJson('/api/enrollments', $enrollmentData)
                ->assertStatus(422)
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Cannot enroll in unpublished course');
        });

        it('student cannot enroll multiple times to same course', function () {
            $enrollmentData = [
                'course_id' => $this->publishedCourse->id
            ];

            postJson('/api/enrollments', $enrollmentData)
                ->assertStatus(422)
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Already enrolled in this course');
        });

        it('student can update their own enrollment', function () {
            putJson("/api/enrollments/{$this->enrollment->id}", [
                'quiz_attempts' => 2
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment updated successfully')
                ->assertJsonPath('data.quiz_attempts', 2);
        });

        it('student can finish their own enrollment', function () {
            postJson("/api/enrollments/{$this->enrollment->id}/finish")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Enrollment finished successfully')
                ->assertJsonPath('data.is_finished', true);
        });

        it('student can view their own enrollment', function () {
            getJson("/api/enrollments/{$this->enrollment->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $this->enrollment->id);
        });

        it('student cannot delete their own enrollment', function () {
            deleteJson("/api/enrollments/{$this->enrollment->id}")
                ->assertForbidden();
        });

        it('student cannot view other student enrollment', function () {
            $otherEnrollment = Enrollment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/enrollments/{$otherEnrollment->id}")
                ->assertForbidden();
        });

        it('student cannot update other student enrollment', function () {
            $otherEnrollment = Enrollment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/enrollments/{$otherEnrollment->id}", [
                'quiz_attempts' => 5
            ])
                ->assertForbidden();
        });
    });
});
