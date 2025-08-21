<?php

use App\Models\Course;
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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        $this->course = Course::factory()->create();
        $this->section = Section::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        actingAs($this->user);
    });

    it('can get all quizzes with pagination', function () {
        Quiz::factory()->count(10)->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        getJson('/api/quizzes')
            ->assertOk()
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

    it('can filter quizzes by section', function () {
        $section2 = Section::factory()->create(['course_id' => $this->course->id]);
        $lesson2 = Lesson::factory()->create([
            'section_id' => $section2->id,
            'course_id' => $this->course->id
        ]);

        Quiz::factory()->count(3)->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        Quiz::factory()->count(2)->create([
            'section_id' => $section2->id,
            'lesson_id' => $lesson2->id,
            'course_id' => $this->course->id
        ]);

        getJson("/api/quizzes?section_id={$this->section->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter quizzes by lesson', function () {
        $lesson2 = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        Quiz::factory()->count(2)->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $lesson2->id,
            'course_id' => $this->course->id
        ]);

        getJson("/api/quizzes?lesson_id={$this->lesson->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('can filter quizzes by course', function () {
        $course2 = Course::factory()->create();
        $section2 = Section::factory()->create(['course_id' => $course2->id]);
        $lesson2 = Lesson::factory()->create([
            'section_id' => $section2->id,
            'course_id' => $course2->id
        ]);

        Quiz::factory()->count(3)->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        Quiz::factory()->create([
            'section_id' => $section2->id,
            'lesson_id' => $lesson2->id,
            'course_id' => $course2->id
        ]);

        getJson("/api/quizzes?course_id={$this->course->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can search quizzes by title', function () {
        Quiz::factory()->create([
            'title' => 'Laravel Advanced Quiz',
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        Quiz::factory()->create([
            'title' => 'Vue.js Basic Quiz',
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        getJson('/api/quizzes?search=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Quiz');
    });

    it('can create quiz', function () {
        $quizData = [
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'title' => 'Test Quiz',
            'instruction' => 'Answer all questions carefully',
            'duration' => '01:30:00',
            'total_marks' => 100,
            'pass_marks' => 60,
            'max_retakes' => 3,
            'min_lesson_taken' => 5
        ];

        postJson('/api/quizzes', $quizData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Test Quiz')
            ->assertJsonPath('data.duration', '01:30:00')
            ->assertJsonPath('data.total_marks', 100)
            ->assertJsonPath('data.pass_marks', 60);

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Test Quiz',
            'total_marks' => 100
        ]);
    });

    it('validates required fields when creating quiz', function () {
        postJson('/api/quizzes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['section_id', 'lesson_id', 'course_id', 'title']);
    });

    it('validates foreign key relationships when creating quiz', function () {
        $quizData = [
            'section_id' => 99999,
            'lesson_id' => 99999,
            'course_id' => 99999,
            'title' => 'Test Quiz'
        ];

        postJson('/api/quizzes', $quizData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['section_id', 'lesson_id', 'course_id']);
    });

    it('can get single quiz with relationships', function () {
        $quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        getJson("/api/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $quiz->id)
            ->assertJsonStructure([
                'data' => [
                    'section',
                    'lesson',
                    'course',
                    'questions'
                ]
            ]);
    });

    it('returns 404 when quiz not found', function () {
        getJson('/api/quizzes/99999')
            ->assertNotFound();
    });

    it('can update quiz', function () {
        $quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        $updateData = [
            'title' => 'Updated Quiz Title',
            'instruction' => 'Updated instruction',
            'total_marks' => 150,
            'pass_marks' => 90
        ];

        putJson("/api/quizzes/{$quiz->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Quiz Title')
            ->assertJsonPath('data.total_marks', 150)
            ->assertJsonPath('data.pass_marks', 90);

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Updated Quiz Title',
            'total_marks' => 150
        ]);
    });

    it('can partially update quiz', function () {
        $quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'title' => 'Original Title'
        ]);

        putJson("/api/quizzes/{$quiz->id}", ['total_marks' => 200])
            ->assertOk()
            ->assertJsonPath('data.title', 'Original Title')
            ->assertJsonPath('data.total_marks', 200);
    });

    it('can delete quiz', function () {
        $quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        deleteJson("/api/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Quiz deleted successfully');

        $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
    });

    it('returns 404 when deleting non-existent quiz', function () {
        deleteJson('/api/quizzes/99999')
            ->assertNotFound();
    });

    it('can set custom per_page for pagination', function () {
        Quiz::factory()->count(10)->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        getJson('/api/quizzes?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('orders quizzes by latest first', function () {
        $older = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'created_at' => now()->subDay()
        ]);

        $newer = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'created_at' => now()
        ]);

        getJson('/api/quizzes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('validates duration format when creating quiz', function () {
        $quizData = [
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'title' => 'Test Quiz',
            'duration' => 'invalid-format'
        ];

        postJson('/api/quizzes', $quizData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration']);
    });

    it('accepts valid duration format', function () {
        $quizData = [
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'title' => 'Test Quiz',
            'duration' => '02:30:00'
        ];

        postJson('/api/quizzes', $quizData)
            ->assertCreated()
            ->assertJsonPath('data.duration', '02:30:00');
    });
});
