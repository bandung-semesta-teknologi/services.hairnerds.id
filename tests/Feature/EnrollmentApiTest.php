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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->published()->verified()->create();
        $this->course->categories()->attach($this->categories->first()->id);

        $this->student = User::factory()->create(['role' => 'student']);
    });

    it('user can get all enrollments with pagination', function () {
        $courses = Course::factory()->published()->verified()->count(3)->create();
        foreach ($courses as $course) {
            Enrollment::factory()->create([
                'user_id' => $this->student->id,
                'course_id' => $course->id
            ]);
        }

        Enrollment::factory()->count(5)->create();

        getJson('/api/enrollments')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user',
                        'course_id',
                        'course',
                        'enrolled_at',
                        'finished_at',
                        'quiz_attempts',
                        'is_finished',
                        'progress',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('user can filter enrollments by user', function () {
        $user2 = User::factory()->create(['role' => 'student']);
        $course2 = Course::factory()->published()->verified()->create();
        $course3 = Course::factory()->published()->verified()->create();

        Enrollment::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);
        Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $course2->id
        ]);
        Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $course3->id
        ]);

        getJson("/api/enrollments?user_id={$this->student->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter enrollments by course', function () {
        $course2 = Course::factory()->published()->verified()->create();
        $course2->categories()->attach($this->categories->last()->id);
        $user2 = User::factory()->create(['role' => 'student']);
        $user3 = User::factory()->create(['role' => 'student']);

        Enrollment::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);
        Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $course2->id
        ]);
        Enrollment::factory()->create([
            'user_id' => $user3->id,
            'course_id' => $course2->id
        ]);

        getJson("/api/enrollments?course_id={$this->course->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter enrollments by status', function () {
        $user2 = User::factory()->create(['role' => 'student']);
        $user3 = User::factory()->create(['role' => 'student']);
        $course2 = Course::factory()->published()->verified()->create();
        $course3 = Course::factory()->published()->verified()->create();

        Enrollment::factory()->active()->count(2)->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);
        Enrollment::factory()->finished()->create([
            'user_id' => $user2->id,
            'course_id' => $course2->id
        ]);
        Enrollment::factory()->finished()->count(2)->create([
            'user_id' => $user3->id,
            'course_id' => $course3->id
        ]);

        getJson('/api/enrollments?status=active')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        getJson('/api/enrollments?status=finished')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('enrollments are ordered by enrolled_at desc', function () {
        $user2 = User::factory()->create(['role' => 'student']);
        $course2 = Course::factory()->published()->verified()->create();

        $older = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now()->subDays(2)
        ]);

        $newer = Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $course2->id,
            'enrolled_at' => now()
        ]);

        getJson('/api/enrollments')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('user can create new enrollment', function () {
        $enrollmentData = [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now()->toDateTimeString(),
            'quiz_attempts' => 0
        ];

        postJson('/api/enrollments', $enrollmentData)
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->student->id)
            ->assertJsonPath('data.course_id', $this->course->id)
            ->assertJsonPath('data.quiz_attempts', 0)
            ->assertJsonPath('data.is_finished', false);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'quiz_attempts' => 0
        ]);
    });

    it('validates required fields when creating enrollment', function () {
        postJson('/api/enrollments', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'course_id']);
    });

    it('validates user exists when creating enrollment', function () {
        postJson('/api/enrollments', [
            'user_id' => 99999,
            'course_id' => $this->course->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    });

    it('validates course exists when creating enrollment', function () {
        postJson('/api/enrollments', [
            'user_id' => $this->student->id,
            'course_id' => 99999
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['course_id']);
    });

    it('user can get single enrollment with relationships', function () {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);

        getJson("/api/enrollments/{$enrollment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $enrollment->id)
            ->assertJsonPath('data.user.name', $this->student->name)
            ->assertJsonPath('data.course.id', $this->course->id);
    });

    it('returns 404 when enrollment not found', function () {
        getJson('/api/enrollments/99999')
            ->assertNotFound();
    });

    it('user can update enrollment', function () {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'quiz_attempts' => 1
        ]);

        $updateData = [
            'quiz_attempts' => 3,
            'finished_at' => now()->toDateTimeString()
        ];

        putJson("/api/enrollments/{$enrollment->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.quiz_attempts', 3)
            ->assertJsonPath('data.is_finished', true);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'quiz_attempts' => 3
        ]);
    });

    it('user can partially update enrollment', function () {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'quiz_attempts' => 1
        ]);

        putJson("/api/enrollments/{$enrollment->id}", ['quiz_attempts' => 5])
            ->assertOk()
            ->assertJsonPath('data.quiz_attempts', 5);
    });

    it('user can delete enrollment', function () {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);

        deleteJson("/api/enrollments/{$enrollment->id}")
            ->assertOk()
            ->assertJson(['message' => 'Enrollment deleted successfully']);

        $this->assertSoftDeleted('enrollments', ['id' => $enrollment->id]);
    });

    it('returns 404 when deleting non-existent enrollment', function () {
        deleteJson('/api/enrollments/99999')
            ->assertNotFound();
    });

    it('user can finish enrollment', function () {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);

        postJson("/api/enrollments/{$enrollment->id}/finish")
            ->assertOk()
            ->assertJsonPath('data.is_finished', true)
            ->assertJsonPath('message', 'Enrollment finished successfully');

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id
        ]);

        $enrollment->refresh();
        expect($enrollment->finished_at)->not()->toBeNull();
    });

    it('user can set custom per_page for pagination', function () {
        $users = User::factory()->count(10)->create(['role' => 'student']);
        $courses = Course::factory()->published()->verified()->count(10)->create();

        foreach ($users as $index => $user) {
            Enrollment::factory()->create([
                'user_id' => $user->id,
                'course_id' => $courses[$index]->id
            ]);
        }

        getJson('/api/enrollments?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('validates enrolled_at date format', function () {
        postJson('/api/enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => 'invalid-date'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['enrolled_at']);
    });

    it('validates finished_at date format on update', function () {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);

        putJson("/api/enrollments/{$enrollment->id}", [
            'finished_at' => 'invalid-date'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['finished_at']);
    });

    it('validates quiz_attempts is not negative', function () {
        postJson('/api/enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'quiz_attempts' => -1
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quiz_attempts']);
    });

    it('defaults enrolled_at to now when not provided', function () {
        $enrollmentData = [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ];

        $response = postJson('/api/enrollments', $enrollmentData)
            ->assertCreated();

        $enrolledAt = $response->json('data.enrolled_at');
        expect($enrolledAt)->not()->toBeEmpty();
    });

    it('defaults quiz_attempts to 0 when not provided', function () {
        $enrollmentData = [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ];

        postJson('/api/enrollments', $enrollmentData)
            ->assertCreated()
            ->assertJsonPath('data.quiz_attempts', 0);
    });
});
