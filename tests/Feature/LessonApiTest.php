<?php

use App\Models\Category;
use App\Models\Course;
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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->create();
        $this->course->categories()->attach($this->categories->first()->id);
        $this->section = Section::factory()->create(['course_id' => $this->course->id]);
    });

    it('user can get all lessons with pagination', function () {
        Lesson::factory()->count(8)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        getJson('/api/lessons')
            ->assertOk()
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

    it('user can filter lessons by section', function () {
        $section1 = Section::factory()->create(['course_id' => $this->course->id]);
        $section2 = Section::factory()->create(['course_id' => $this->course->id]);

        Lesson::factory()->count(3)->create([
            'section_id' => $section1->id,
            'course_id' => $this->course->id
        ]);
        Lesson::factory()->count(2)->create([
            'section_id' => $section2->id,
            'course_id' => $this->course->id
        ]);

        getJson("/api/lessons?section_id={$section1->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter lessons by course', function () {
        $course2 = Course::factory()->create();
        $course2->categories()->attach($this->categories->last()->id);
        $section2 = Section::factory()->create(['course_id' => $course2->id]);

        Lesson::factory()->count(3)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        Lesson::factory()->count(2)->create([
            'section_id' => $section2->id,
            'course_id' => $course2->id
        ]);

        getJson("/api/lessons?course_id={$this->course->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter lessons by type', function () {
        Lesson::factory()->youtube()->count(2)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        Lesson::factory()->document()->count(3)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        getJson('/api/lessons?type=youtube')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('lessons are ordered by sequence', function () {
        Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'sequence' => 3
        ]);
        Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'sequence' => 1
        ]);
        Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'sequence' => 2
        ]);

        getJson("/api/lessons?section_id={$this->section->id}")
            ->assertOk()
            ->assertJsonPath('data.0.sequence', 1)
            ->assertJsonPath('data.1.sequence', 2)
            ->assertJsonPath('data.2.sequence', 3);
    });

    it('user can create new lesson', function () {
        $lessonData = [
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'sequence' => 1,
            'type' => 'youtube',
            'title' => 'Introduction Video',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'summary' => 'This is an introduction video',
            'datetime' => now()->toDateTimeString()
        ];

        postJson('/api/lessons', $lessonData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Introduction Video')
            ->assertJsonPath('data.type', 'youtube')
            ->assertJsonPath('data.sequence', 1)
            ->assertJsonPath('data.section_id', $this->section->id)
            ->assertJsonPath('data.course_id', $this->course->id);

        $this->assertDatabaseHas('lessons', [
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'title' => 'Introduction Video',
            'type' => 'youtube'
        ]);
    });

    it('validates required fields when creating lesson', function () {
        postJson('/api/lessons', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['section_id', 'course_id', 'sequence', 'type', 'title', 'url']);
    });

    it('validates lesson type enum', function () {
        postJson('/api/lessons', [
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'sequence' => 1,
            'type' => 'invalid_type',
            'title' => 'Test Lesson',
            'url' => 'https://example.com'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('user can get single lesson with relationships', function () {
        $lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        getJson("/api/lessons/{$lesson->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $lesson->id)
            ->assertJsonPath('data.title', $lesson->title)
            ->assertJsonPath('data.type', $lesson->type)
            ->assertJsonPath('data.section.id', $this->section->id)
            ->assertJsonPath('data.course.id', $this->course->id);
    });

    it('returns 404 when lesson not found', function () {
        getJson('/api/lessons/99999')
            ->assertNotFound();
    });

    it('user can update lesson', function () {
        $lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $updateData = [
            'title' => 'Updated Lesson Title',
            'type' => 'document',
            'url' => 'https://example.com/updated.pdf',
            'sequence' => 5
        ];

        putJson("/api/lessons/{$lesson->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Lesson Title')
            ->assertJsonPath('data.type', 'document')
            ->assertJsonPath('data.sequence', 5);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Updated Lesson Title',
            'type' => 'document',
            'sequence' => 5
        ]);
    });

    it('user can delete lesson', function () {
        $lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        deleteJson("/api/lessons/{$lesson->id}")
            ->assertOk()
            ->assertJson(['message' => 'Lesson deleted successfully']);

        $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
    });

    it('returns 404 when deleting non-existent lesson', function () {
        deleteJson('/api/lessons/99999')
            ->assertNotFound();
    });

    it('user can set custom per_page for pagination', function () {
        Lesson::factory()->count(10)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        getJson('/api/lessons?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });
});
