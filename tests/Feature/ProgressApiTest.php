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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->published()->verified()->create();
        $this->course->categories()->attach($this->categories->first()->id);

        $this->section = Section::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $this->student = User::factory()->create(['role' => 'student']);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);
    });

    it('user can get all progress with pagination', function () {
        $lessons = Lesson::factory()->count(8)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        foreach ($lessons as $lesson) {
            Progress::factory()->create([
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->course->id,
                'lesson_id' => $lesson->id
            ]);
        }

        getJson('/api/progress')
            ->assertOk()
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

    it('user can filter progress by enrollment', function () {
        $user2 = User::factory()->create(['role' => 'student']);
        $enrollment2 = Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $this->course->id
        ]);
        $lesson2 = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $lesson3 = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        Progress::factory()->count(3)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);
        Progress::factory()->create([
            'enrollment_id' => $enrollment2->id,
            'user_id' => $user2->id,
            'course_id' => $this->course->id,
            'lesson_id' => $lesson2->id
        ]);
        Progress::factory()->create([
            'enrollment_id' => $enrollment2->id,
            'user_id' => $user2->id,
            'course_id' => $this->course->id,
            'lesson_id' => $lesson3->id
        ]);

        getJson("/api/progress?enrollment_id={$this->enrollment->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter progress by user', function () {
        $user2 = User::factory()->create(['role' => 'student']);
        $course2 = Course::factory()->published()->verified()->create();
        $section2 = Section::factory()->create(['course_id' => $course2->id]);
        $lesson2 = Lesson::factory()->create([
            'section_id' => $section2->id,
            'course_id' => $course2->id
        ]);
        $enrollment2 = Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $course2->id
        ]);

        $lessons = Lesson::factory()->count(3)->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        foreach ($lessons as $lesson) {
            Progress::factory()->create([
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->student->id,
                'course_id' => $this->course->id,
                'lesson_id' => $lesson->id
            ]);
        }

        Progress::factory()->create([
            'enrollment_id' => $enrollment2->id,
            'user_id' => $user2->id,
            'course_id' => $course2->id,
            'lesson_id' => $lesson2->id
        ]);
        Progress::factory()->create([
            'enrollment_id' => $enrollment2->id,
            'user_id' => $user2->id,
            'course_id' => $course2->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson("/api/progress?user_id={$this->student->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter progress by course', function () {
        $course2 = Course::factory()->published()->verified()->create();
        $section2 = Section::factory()->create(['course_id' => $course2->id]);
        $lesson2 = Lesson::factory()->create([
            'section_id' => $section2->id,
            'course_id' => $course2->id
        ]);
        $enrollment2 = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $course2->id
        ]);

        Progress::factory()->count(3)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);
        Progress::factory()->count(2)->create([
            'enrollment_id' => $enrollment2->id,
            'user_id' => $this->student->id,
            'course_id' => $course2->id,
            'lesson_id' => $lesson2->id
        ]);

        getJson("/api/progress?course_id={$this->course->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter progress by lesson', function () {
        $lesson2 = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        Progress::factory()->count(3)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);
        Progress::factory()->count(2)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $lesson2->id
        ]);

        getJson("/api/progress?lesson_id={$this->lesson->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter progress by status', function () {
        Progress::factory()->completed()->count(2)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);
        Progress::factory()->incomplete()->count(3)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson('/api/progress?status=completed')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        getJson('/api/progress?status=incomplete')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('progress are ordered by latest first', function () {
        $older = Progress::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'created_at' => now()->subDay()
        ]);

        $newer = Progress::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'created_at' => now()
        ]);

        getJson('/api/progress')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('user can create new progress', function () {
        $progressData = [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'is_completed' => false,
            'score' => null
        ];

        postJson('/api/progress', $progressData)
            ->assertCreated()
            ->assertJsonPath('data.enrollment_id', $this->enrollment->id)
            ->assertJsonPath('data.user_id', $this->student->id)
            ->assertJsonPath('data.course_id', $this->course->id)
            ->assertJsonPath('data.lesson_id', $this->lesson->id)
            ->assertJsonPath('data.is_completed', false);

        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);
    });

    it('validates required fields when creating progress', function () {
        postJson('/api/progress', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['enrollment_id', 'user_id', 'course_id', 'lesson_id']);
    });

    it('validates foreign key relationships when creating progress', function () {
        $progressData = [
            'enrollment_id' => 99999,
            'user_id' => 99999,
            'course_id' => 99999,
            'lesson_id' => 99999
        ];

        postJson('/api/progress', $progressData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['enrollment_id', 'user_id', 'course_id', 'lesson_id']);
    });

    it('user can get single progress with relationships', function () {
        $progress = Progress::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson("/api/progress/{$progress->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $progress->id)
            ->assertJsonPath('data.enrollment.id', $this->enrollment->id)
            ->assertJsonPath('data.user.name', $this->student->name)
            ->assertJsonPath('data.course.id', $this->course->id)
            ->assertJsonPath('data.lesson.id', $this->lesson->id);
    });

    it('returns 404 when progress not found', function () {
        getJson('/api/progress/99999')
            ->assertNotFound();
    });

    it('user can update progress', function () {
        $progress = Progress::factory()->incomplete()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);

        $updateData = [
            'is_completed' => true,
            'score' => 85
        ];

        putJson("/api/progress/{$progress->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.score', 85);

        $this->assertDatabaseHas('progress', [
            'id' => $progress->id,
            'is_completed' => true,
            'score' => 85
        ]);
    });

    it('user can partially update progress', function () {
        $progress = Progress::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'score' => 70
        ]);

        putJson("/api/progress/{$progress->id}", ['score' => 95])
            ->assertOk()
            ->assertJsonPath('data.score', 95);
    });

    it('user can delete progress', function () {
        $progress = Progress::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);

        deleteJson("/api/progress/{$progress->id}")
            ->assertOk()
            ->assertJson(['message' => 'Progress deleted successfully']);

        $this->assertSoftDeleted('progress', ['id' => $progress->id]);
    });

    it('returns 404 when deleting non-existent progress', function () {
        deleteJson('/api/progress/99999')
            ->assertNotFound();
    });

    it('user can complete progress', function () {
        $progress = Progress::factory()->incomplete()->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);

        postJson("/api/progress/{$progress->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('message', 'Progress marked as completed');

        $this->assertDatabaseHas('progress', [
            'id' => $progress->id,
            'is_completed' => true
        ]);
    });

    it('user can set custom per_page for pagination', function () {
        Progress::factory()->count(10)->create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ]);

        getJson('/api/progress?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('validates score is not negative', function () {
        postJson('/api/progress', [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'score' => -5
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['score']);
    });

    it('validates is_completed as boolean', function () {
        postJson('/api/progress', [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'is_completed' => 'not_boolean'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_completed']);
    });

    it('accepts boolean values as string for is_completed', function () {
        postJson('/api/progress', [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'is_completed' => '1'
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_completed', true);
    });

    it('defaults is_completed to false when not provided', function () {
        postJson('/api/progress', [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_completed', false);
    });
});
