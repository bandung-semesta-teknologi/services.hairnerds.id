<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('progress crud api', function () {
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
        $this->otherEnrollment = Enrollment::factory()->create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->otherCourse->id
        ]);

        $this->progress = Progress::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id,
            'lesson_id' => $this->publishedLesson->id
        ]);
    });

    describe('guest access (forbidden)', function () {
        it('guest user cannot view progress', function () {
            getJson('/api/progress')
                ->assertUnauthorized();
        });

        it('guest user cannot view single progress', function () {
            getJson("/api/progress/{$this->progress->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot create progress', function () {
            postJson('/api/progress', [
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id,
                'lesson_id' => $this->publishedLesson->id
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update progress', function () {
            putJson("/api/progress/{$this->progress->id}", [
                'is_completed' => true
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete progress', function () {
            deleteJson("/api/progress/{$this->progress->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot complete progress', function () {
            postJson("/api/progress/{$this->progress->id}/complete")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all progress from all courses', function () {
            Progress::factory()->count(3)->create([
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id,
                'lesson_id' => $this->publishedLesson->id
            ]);
            Progress::factory()->count(2)->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson('/api/progress')
                ->assertOk()
                ->assertJsonCount(6, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'enrollment_id',
                            'enrollment',
                            'user_id',
                            'user',
                            'course_id',
                            'course',
                            'lesson_id',
                            'lesson',
                            'is_completed',
                            'score',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can create progress for any enrollment', function () {
            $progressData = [
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id,
                'is_completed' => false
            ];

            postJson('/api/progress', $progressData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress created successfully')
                ->assertJsonPath('data.user_id', $this->otherStudent->id)
                ->assertJsonPath('data.course_id', $this->otherCourse->id)
                ->assertJsonPath('data.is_completed', false);

            $this->assertDatabaseHas('progress', [
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);
        });

        it('admin can update any progress', function () {
            $updateData = [
                'is_completed' => true,
                'score' => 85
            ];

            putJson("/api/progress/{$this->progress->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress updated successfully')
                ->assertJsonPath('data.is_completed', true)
                ->assertJsonPath('data.score', 85);

            $this->assertDatabaseHas('progress', [
                'id' => $this->progress->id,
                'is_completed' => true,
                'score' => 85
            ]);
        });

        it('admin can delete any progress', function () {
            deleteJson("/api/progress/{$this->progress->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress deleted successfully');

            $this->assertSoftDeleted('progress', ['id' => $this->progress->id]);
        });

        it('admin can complete any progress', function () {
            postJson("/api/progress/{$this->progress->id}/complete")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress marked as completed')
                ->assertJsonPath('data.is_completed', true);

            $this->assertDatabaseHas('progress', [
                'id' => $this->progress->id,
                'is_completed' => true
            ]);
        });

        it('admin can view any progress', function () {
            getJson("/api/progress/{$this->progress->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $this->progress->id)
                ->assertJsonStructure([
                    'data' => [
                        'enrollment',
                        'user',
                        'course',
                        'lesson'
                    ]
                ]);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see progress only from their own courses', function () {
            Progress::factory()->count(3)->create([
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id,
                'lesson_id' => $this->publishedLesson->id
            ]);
            Progress::factory()->count(2)->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson('/api/progress')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });

        it('instructor can create progress for their own course', function () {
            $newEnrollment = Enrollment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id
            ]);

            $progressData = [
                'enrollment_id' => $newEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->publishedCourse->id,
                'lesson_id' => $this->publishedLesson->id
            ];

            postJson('/api/progress', $progressData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress created successfully')
                ->assertJsonPath('data.user_id', $this->otherStudent->id);
        });

        it('instructor can update progress from their own course', function () {
            putJson("/api/progress/{$this->progress->id}", [
                'is_completed' => true,
                'score' => 90
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress updated successfully')
                ->assertJsonPath('data.is_completed', true);
        });

        it('instructor can delete progress from their own course', function () {
            deleteJson("/api/progress/{$this->progress->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress deleted successfully');

            $this->assertSoftDeleted('progress', ['id' => $this->progress->id]);
        });

        it('instructor can complete progress from their own course', function () {
            postJson("/api/progress/{$this->progress->id}/complete")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress marked as completed')
                ->assertJsonPath('data.is_completed', true);
        });

        it('instructor can view progress from their own course', function () {
            getJson("/api/progress/{$this->progress->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $this->progress->id);
        });

        it('instructor cannot create progress for other instructor course', function () {
            $progressData = [
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ];

            postJson('/api/progress', $progressData)
                ->assertForbidden();
        });

        it('instructor cannot update progress from other instructor course', function () {
            $otherProgress = Progress::factory()->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            putJson("/api/progress/{$otherProgress->id}", [
                'is_completed' => true
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete progress from other instructor course', function () {
            $otherProgress = Progress::factory()->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            deleteJson("/api/progress/{$otherProgress->id}")
                ->assertForbidden();
        });

        it('instructor cannot view progress from other instructor course', function () {
            $otherProgress = Progress::factory()->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson("/api/progress/{$otherProgress->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view their own progress only', function () {
            Progress::factory()->count(2)->create([
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id,
                'lesson_id' => $this->publishedLesson->id
            ]);
            Progress::factory()->count(3)->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson('/api/progress')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can complete their own progress', function () {
            postJson("/api/progress/{$this->progress->id}/complete")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Progress marked as completed')
                ->assertJsonPath('data.is_completed', true);

            $this->assertDatabaseHas('progress', [
                'id' => $this->progress->id,
                'is_completed' => true
            ]);
        });

        it('student can view their own progress', function () {
            getJson("/api/progress/{$this->progress->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $this->progress->id);
        });

        it('student cannot create progress', function () {
            postJson('/api/progress', [
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->publishedCourse->id,
                'lesson_id' => $this->publishedLesson->id
            ])
                ->assertForbidden();
        });

        it('student cannot update progress', function () {
            putJson("/api/progress/{$this->progress->id}", [
                'is_completed' => true
            ])
                ->assertForbidden();
        });

        it('student cannot delete progress', function () {
            deleteJson("/api/progress/{$this->progress->id}")
                ->assertForbidden();
        });

        it('student cannot view other student progress', function () {
            $otherProgress = Progress::factory()->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            getJson("/api/progress/{$otherProgress->id}")
                ->assertForbidden();
        });

        it('student cannot complete other student progress', function () {
            $otherProgress = Progress::factory()->create([
                'enrollment_id' => $this->otherEnrollment->id,
                'user_id' => $this->otherStudent->id,
                'course_id' => $this->otherCourse->id,
                'lesson_id' => $this->otherLesson->id
            ]);

            postJson("/api/progress/{$otherProgress->id}/complete")
                ->assertForbidden();
        });
    });
});
