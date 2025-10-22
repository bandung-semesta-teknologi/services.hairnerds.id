<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('section crud api', function () {
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

        $this->draftCourse = Course::factory()->draft()->create();
        $this->draftCourse->categories()->attach($this->categories->last()->id);
        $this->draftCourse->instructors()->attach($this->instructor->id);

        $this->otherInstructorCourse = Course::factory()->published()->create();
        $this->otherInstructorCourse->instructors()->attach($this->otherInstructor->id);
    });

    describe('guest access', function () {
        it('anyone can get sections from published courses without auth', function () {
            Section::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            Section::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/academy/sections')
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'course_id',
                            'course',
                            'sequence',
                            'title',
                            'objective',
                            'lessons',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('anyone can filter sections by published course without auth', function () {
            Section::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            Section::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/academy/sections?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');

            getJson("/api/academy/sections?course_id={$this->draftCourse->id}")
                ->assertOk()
                ->assertJsonCount(0, 'data');
        });

        it('sections are ordered by sequence', function () {
            Section::factory()->create(['course_id' => $this->publishedCourse->id, 'sequence' => 3]);
            Section::factory()->create(['course_id' => $this->publishedCourse->id, 'sequence' => 1]);
            Section::factory()->create(['course_id' => $this->publishedCourse->id, 'sequence' => 2]);

            getJson("/api/academy/sections?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonPath('data.0.sequence', 1)
                ->assertJsonPath('data.1.sequence', 2)
                ->assertJsonPath('data.2.sequence', 3);
        });

        it('anyone can get single section from published course without auth', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $section->id)
                ->assertJsonPath('data.title', $section->title)
                ->assertJsonPath('data.sequence', $section->sequence)
                ->assertJsonPath('data.course.id', $this->publishedCourse->id);
        });

        it('cannot get section from draft course without auth', function () {
            $section = Section::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertForbidden();
        });

        it('returns 404 when section not found', function () {
            getJson('/api/academy/sections/99999')
                ->assertNotFound();
        });

        it('anyone can set custom per_page for pagination', function () {
            Section::factory()->count(10)->create(['course_id' => $this->publishedCourse->id]);

            getJson('/api/academy/sections?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all sections including draft courses', function () {
            Section::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            Section::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/academy/sections')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('admin can view section from draft course', function () {
            $section = Section::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $section->id);
        });

        it('admin can create new section', function () {
            $sectionData = [
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'title' => 'Introduction',
                'objective' => 'Learn the basics'
            ];

            postJson('/api/academy/sections', $sectionData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Introduction')
                ->assertJsonPath('data.objective', 'Learn the basics')
                ->assertJsonPath('data.sequence', 1)
                ->assertJsonPath('data.course_id', $this->publishedCourse->id);

            $this->assertDatabaseHas('sections', [
                'course_id' => $this->publishedCourse->id,
                'title' => 'Introduction',
                'sequence' => 1
            ]);
        });

        it('validates required fields when creating section', function () {
            postJson('/api/academy/sections', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['course_id', 'sequence', 'title']);
        });

        it('admin can update any section', function () {
            $section = Section::factory()->create(['course_id' => $this->draftCourse->id]);

            $updateData = [
                'title' => 'Updated Section Title',
                'objective' => 'Updated objective',
                'sequence' => 5
            ];

            putJson("/api/academy/sections/{$section->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('data.title', 'Updated Section Title')
                ->assertJsonPath('data.objective', 'Updated objective')
                ->assertJsonPath('data.sequence', 5);

            $this->assertDatabaseHas('sections', [
                'id' => $section->id,
                'title' => 'Updated Section Title',
                'sequence' => 5
            ]);
        });

        it('admin can delete any section', function () {
            $section = Section::factory()->create(['course_id' => $this->draftCourse->id]);

            deleteJson("/api/academy/sections/{$section->id}")
                ->assertOk()
                ->assertJson(['message' => 'Section deleted successfully']);

            $this->assertSoftDeleted('sections', ['id' => $section->id]);
        });

        it('returns 404 when deleting non-existent section', function () {
            deleteJson('/api/academy/sections/99999')
                ->assertNotFound();
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see sections from their own courses only', function () {
            Section::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            Section::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);
            Section::factory()->count(1)->create(['course_id' => $this->otherInstructorCourse->id]);

            getJson('/api/academy/sections')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can view section from their own course', function () {
            $section = Section::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $section->id);
        });

        it('instructor cannot view section from other instructor course', function () {
            $section = Section::factory()->create(['course_id' => $this->otherInstructorCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertForbidden();
        });

        it('instructor can create section for their own course', function () {
            $sectionData = [
                'course_id' => $this->publishedCourse->id,
                'sequence' => 2,
                'title' => 'Advanced Topics',
                'objective' => 'Master advanced concepts'
            ];

            postJson('/api/academy/sections', $sectionData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Advanced Topics')
                ->assertJsonPath('data.objective', 'Master advanced concepts');
        });

        it('instructor can update section from their own course', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/academy/sections/{$section->id}", [
                'title' => 'Instructor Updated Section',
                'sequence' => 10
            ])
                ->assertOk()
                ->assertJsonPath('data.title', 'Instructor Updated Section')
                ->assertJsonPath('data.sequence', 10);
        });

        it('instructor cannot update section from other instructor course', function () {
            $section = Section::factory()->create(['course_id' => $this->otherInstructorCourse->id]);

            putJson("/api/academy/sections/{$section->id}", [
                'title' => 'Unauthorized Update'
            ])
                ->assertForbidden();
        });

        it('instructor can delete section from their own course', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/academy/sections/{$section->id}")
                ->assertOk()
                ->assertJson(['message' => 'Section deleted successfully']);

            $this->assertSoftDeleted('sections', ['id' => $section->id]);
        });

        it('instructor cannot delete section from other instructor course', function () {
            $section = Section::factory()->create(['course_id' => $this->otherInstructorCourse->id]);

            deleteJson("/api/academy/sections/{$section->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can see sections from published courses only', function () {
            Section::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            Section::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);
            Section::factory()->count(1)->create(['course_id' => $this->otherInstructorCourse->id]);

            getJson('/api/academy/sections')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });

        it('student can view section from published course', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $section->id);
        });

        it('student cannot view section from draft course', function () {
            $section = Section::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/academy/sections/{$section->id}")
                ->assertForbidden();
        });

        it('student cannot create section', function () {
            postJson('/api/academy/sections', [
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'title' => 'Unauthorized Section'
            ])
                ->assertForbidden();
        });

        it('student cannot update section', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/academy/sections/{$section->id}", [
                'title' => 'Unauthorized Update'
            ])
                ->assertForbidden();
        });

        it('student cannot delete section', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/academy/sections/{$section->id}")
                ->assertForbidden();
        });
    });

    describe('guest access', function () {
        it('guest user cannot create section', function () {
            postJson('/api/academy/sections', [
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'title' => 'Unauthorized Section'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update section', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/academy/sections/{$section->id}", [
                'title' => 'Unauthorized Update'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete section', function () {
            $section = Section::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/academy/sections/{$section->id}")
                ->assertUnauthorized();
        });
    });
});
