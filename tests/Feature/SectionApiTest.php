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

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->create();
        $this->course->categories()->attach($this->categories->first()->id);
    });

    describe('public access', function () {
        it('anyone can get all sections with pagination without auth', function () {
            Section::factory()->count(8)->create(['course_id' => $this->course->id]);

            getJson('/api/sections')
                ->assertOk()
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

        it('anyone can filter sections by course without auth', function () {
            $course1 = Course::factory()->create();
            $course2 = Course::factory()->create();

            $course1->categories()->attach($this->categories->first()->id);
            $course2->categories()->attach($this->categories->last()->id);

            Section::factory()->count(3)->create(['course_id' => $course1->id]);
            Section::factory()->count(2)->create(['course_id' => $course2->id]);

            getJson("/api/sections?course_id={$course1->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('sections are ordered by sequence', function () {
            Section::factory()->create(['course_id' => $this->course->id, 'sequence' => 3]);
            Section::factory()->create(['course_id' => $this->course->id, 'sequence' => 1]);
            Section::factory()->create(['course_id' => $this->course->id, 'sequence' => 2]);

            getJson("/api/sections?course_id={$this->course->id}")
                ->assertOk()
                ->assertJsonPath('data.0.sequence', 1)
                ->assertJsonPath('data.1.sequence', 2)
                ->assertJsonPath('data.2.sequence', 3);
        });

        it('anyone can get single section with course relationship without auth', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            getJson("/api/sections/{$section->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $section->id)
                ->assertJsonPath('data.title', $section->title)
                ->assertJsonPath('data.sequence', $section->sequence)
                ->assertJsonPath('data.course.id', $this->course->id);
        });

        it('returns 404 when section not found', function () {
            getJson('/api/sections/99999')
                ->assertNotFound();
        });

        it('anyone can set custom per_page for pagination', function () {
            Section::factory()->count(10)->create(['course_id' => $this->course->id]);

            getJson('/api/sections?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can create new section', function () {
            $sectionData = [
                'course_id' => $this->course->id,
                'sequence' => 1,
                'title' => 'Introduction',
                'objective' => 'Learn the basics'
            ];

            postJson('/api/sections', $sectionData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Introduction')
                ->assertJsonPath('data.objective', 'Learn the basics')
                ->assertJsonPath('data.sequence', 1)
                ->assertJsonPath('data.course_id', $this->course->id);

            $this->assertDatabaseHas('sections', [
                'course_id' => $this->course->id,
                'title' => 'Introduction',
                'sequence' => 1
            ]);
        });

        it('validates required fields when creating section', function () {
            postJson('/api/sections', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['course_id', 'sequence', 'title']);
        });

        it('admin can update section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            $updateData = [
                'title' => 'Updated Section Title',
                'objective' => 'Updated objective',
                'sequence' => 5
            ];

            putJson("/api/sections/{$section->id}", $updateData)
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

        it('admin can delete section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/sections/{$section->id}")
                ->assertOk()
                ->assertJson(['message' => 'Section deleted successfully']);

            $this->assertSoftDeleted('sections', ['id' => $section->id]);
        });

        it('returns 404 when deleting non-existent section', function () {
            deleteJson('/api/sections/99999')
                ->assertNotFound();
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can create new section', function () {
            $sectionData = [
                'course_id' => $this->course->id,
                'sequence' => 2,
                'title' => 'Advanced Topics',
                'objective' => 'Master advanced concepts'
            ];

            postJson('/api/sections', $sectionData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Advanced Topics')
                ->assertJsonPath('data.objective', 'Master advanced concepts');
        });

        it('instructor can update section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            putJson("/api/sections/{$section->id}", [
                'title' => 'Instructor Updated Section',
                'sequence' => 10
            ])
                ->assertOk()
                ->assertJsonPath('data.title', 'Instructor Updated Section')
                ->assertJsonPath('data.sequence', 10);
        });

        it('instructor can delete section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/sections/{$section->id}")
                ->assertOk()
                ->assertJson(['message' => 'Section deleted successfully']);

            $this->assertSoftDeleted('sections', ['id' => $section->id]);
        });
    });

    describe('student access (forbidden)', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student cannot create section', function () {
            postJson('/api/sections', [
                'course_id' => $this->course->id,
                'sequence' => 1,
                'title' => 'Unauthorized Section'
            ])
                ->assertForbidden();
        });

        it('student cannot update section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            putJson("/api/sections/{$section->id}", [
                'title' => 'Unauthorized Update'
            ])
                ->assertForbidden();
        });

        it('student cannot delete section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/sections/{$section->id}")
                ->assertForbidden();
        });
    });

    describe('unauthenticated access (forbidden)', function () {
        it('unauthenticated user cannot create section', function () {
            postJson('/api/sections', [
                'course_id' => $this->course->id,
                'sequence' => 1,
                'title' => 'Unauthorized Section'
            ])
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot update section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            putJson("/api/sections/{$section->id}", [
                'title' => 'Unauthorized Update'
            ])
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot delete section', function () {
            $section = Section::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/sections/{$section->id}")
                ->assertUnauthorized();
        });
    });
});
