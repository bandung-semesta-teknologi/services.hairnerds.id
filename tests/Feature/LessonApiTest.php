<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('lesson crud api', function () {
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

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('unauthenticated access (forbidden)', function () {
        it('unauthenticated user cannot view lessons', function () {
            getJson('/api/lessons')
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot view single lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot create lesson', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Introduction Video',
                'url' => 'https://youtube.com/watch?v=example'
            ])
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot update lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Updated title'
            ])
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot delete lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all lessons from all courses', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/lessons')
                ->assertOk()
                ->assertJsonCount(7, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'section_id',
                            'section',
                            'course_id',
                            'course',
                            'sequence',
                            'type',
                            'title',
                            'url',
                            'summary',
                            'datetime',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can create new lesson', function () {
            $lessonData = [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Introduction to Laravel',
                'url' => 'https://youtube.com/watch?v=example',
                'summary' => 'Basic introduction to Laravel framework'
            ];

            postJson('/api/lessons', $lessonData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson created successfully')
                ->assertJsonPath('data.title', 'Introduction to Laravel')
                ->assertJsonPath('data.type', 'youtube')
                ->assertJsonPath('data.sequence', 1);

            $this->assertDatabaseHas('lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Introduction to Laravel',
                'type' => 'youtube'
            ]);
        });

        it('validates required fields when creating lesson', function () {
            postJson('/api/lessons', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['section_id', 'course_id', 'sequence', 'type', 'title', 'url']);
        });

        it('admin can update any lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            $updateData = [
                'title' => 'Updated Lesson Title',
                'summary' => 'Updated lesson summary'
            ];

            putJson("/api/lessons/{$lesson->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson updated successfully')
                ->assertJsonPath('data.title', 'Updated Lesson Title')
                ->assertJsonPath('data.summary', 'Updated lesson summary');

            $this->assertDatabaseHas('lessons', [
                'id' => $lesson->id,
                'title' => 'Updated Lesson Title',
                'summary' => 'Updated lesson summary'
            ]);
        });

        it('admin can delete any lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson deleted successfully');

            $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
        });

        it('admin can view any lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see lessons only from their own courses', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/lessons')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can create lesson for their own course', function () {
            $lessonData = [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'document',
                'title' => 'Course Materials PDF',
                'url' => 'https://example.com/materials.pdf'
            ];

            postJson('/api/lessons', $lessonData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson created successfully')
                ->assertJsonPath('data.title', 'Course Materials PDF')
                ->assertJsonPath('data.type', 'document');
        });

        it('instructor can update lesson from their own course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Instructor Updated Lesson',
                'summary' => 'Updated by instructor'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson updated successfully')
                ->assertJsonPath('data.title', 'Instructor Updated Lesson');
        });

        it('instructor can delete lesson from their own course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson deleted successfully');

            $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
        });

        it('instructor can view lesson from their own course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id);
        });

        it('instructor cannot update lesson from other instructor course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete lesson from other instructor course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('instructor cannot view lesson from other instructor course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view lessons from enrolled published courses only', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/lessons')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view single lesson from enrolled published course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id);
        });

        it('student cannot view lesson from non-enrolled course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('student cannot view lesson from draft course even if enrolled', function () {
            Enrollment::factory()->create([
                'user_id' => $this->student->id,
                'course_id' => $this->draftCourse->id
            ]);

            $lesson = Lesson::factory()->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('student can filter lessons by enrolled course', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can filter lessons by section', function () {
            Lesson::factory()->count(2)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons?section_id={$this->publishedSection->id}")
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('student cannot create lesson', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Unauthorized lesson',
                'url' => 'https://youtube.com/watch?v=example'
            ])
                ->assertForbidden();
        });

        it('student cannot update lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            putJson("/api/lessons/{$lesson->id}", [
                'title' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('student cannot delete lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('returns 404 when lesson not found', function () {
            getJson('/api/lessons/99999')
                ->assertNotFound();
        });

        it('student can set custom per_page for pagination', function () {
            Lesson::factory()->count(10)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson('/api/lessons?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });
});
