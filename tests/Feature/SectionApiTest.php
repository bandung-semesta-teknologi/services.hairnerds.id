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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->create();
        $this->course->categories()->attach($this->categories->first()->id);
    });

    it('user can get all sections with pagination', function () {
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
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('user can filter sections by course', function () {
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

    it('user can create new section', function () {
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

    it('user can get single section with course relationship', function () {
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

    it('user can update section', function () {
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

    it('user can delete section', function () {
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

    it('user can set custom per_page for pagination', function () {
        Section::factory()->count(10)->create(['course_id' => $this->course->id]);

        getJson('/api/sections?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });
});
