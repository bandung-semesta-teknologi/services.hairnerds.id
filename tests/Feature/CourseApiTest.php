<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('course crud api', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        $this->categories = Category::factory()->count(3)->create();
    });

    describe('guest access', function () {
        it('guest can only see published courses', function () {
            Course::factory()->count(3)->published()->create()->each(function ($course) {
                $course->categories()->attach($this->categories->random(2)->pluck('id'));
            });
            Course::factory()->count(2)->draft()->create()->each(function ($course) {
                $course->categories()->attach($this->categories->random(2)->pluck('id'));
            });

            getJson('/api/courses')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('guest can get published course details', function () {
            $course = Course::factory()->published()->create();
            $course->categories()->attach($this->categories->take(2)->pluck('id'));

            getJson("/api/courses/{$course->id}")
                ->assertOk()
                ->assertJsonPath('data.status', 'published');
        });

        it('guest cannot see draft course details', function () {
            $draftCourse = Course::factory()->draft()->create();

            getJson("/api/courses/{$draftCourse->id}")
                ->assertForbidden();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            Course::query()->forceDelete();
            $this->admin = User::factory()->admin()->create();
            actingAs($this->admin);
        });

        it('admin can see all courses regardless of status', function () {
            $instructor = User::factory()->instructor()->create();

            Course::factory()->count(2)->published()->create()->each(function ($course) use ($instructor) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($instructor->id);
            });

            Course::factory()->count(2)->draft()->create()->each(function ($course) use ($instructor) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($instructor->id);
            });

            Course::factory()->count(1)->takedown()->create()->each(function ($course) use ($instructor) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($instructor->id);
            });

            getJson('/api/courses')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('admin can filter courses by status', function () {
            $instructor = User::factory()->instructor()->create();

            Course::factory()->count(2)->published()->create()->each(function ($course) use ($instructor) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($instructor->id);
            });

            Course::factory()->count(3)->draft()->create()->each(function ($course) use ($instructor) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($instructor->id);
            });

            getJson('/api/courses?status=draft')
                ->assertOk()
                ->assertJsonCount(3, 'data');

            getJson('/api/courses?status=published')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('admin can create course', function () {
            $courseData = [
                'title' => 'Test Course',
                'short_description' => 'This is a test course',
                'category_ids' => [$this->categories->first()->id],
                'instructor_ids' => [User::factory()->instructor()->create()->id],
                'level' => 'intermediate',
                'lang' => 'english',
                'price' => 99,
            ];

            postJson('/api/courses', $courseData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Test Course');
        });

        it('admin can verify draft course', function () {
            $draftCourse = Course::factory()->draft()->create(['verified_at' => null]);

            postJson("/api/courses/{$draftCourse->id}/verify", [
                'status' => 'published'
            ])
                ->assertOk()
                ->assertJsonPath('data.status', 'published');
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            Course::query()->forceDelete();
            $this->instructor = User::factory()->instructor()->create();
            actingAs($this->instructor);
        });

        it('instructor can see only their own courses', function () {
            $otherInstructor = User::factory()->instructor()->create();

            Course::factory()->count(2)->published()->create()->each(function ($course) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($this->instructor->id);
            });

            Course::factory()->count(3)->published()->create()->each(function ($course) use ($otherInstructor) {
                $course->categories()->attach($this->categories->first()->id);
                $course->instructors()->attach($otherInstructor->id);
            });

            getJson('/api/courses')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('instructor can create course', function () {
            $courseData = [
                'title' => 'Instructor Course',
                'category_ids' => [$this->categories->first()->id],
                'level' => 'beginner',
                'lang' => 'english',
            ];

            postJson('/api/courses', $courseData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Instructor Course')
                ->assertJsonPath('data.status', 'draft');
        });

        it('instructor can update own course', function () {
            $course = Course::factory()->draft()->create();
            $course->categories()->attach($this->categories->first()->id);
            $course->instructors()->attach($this->instructor->id);

            putJson("/api/courses/{$course->id}", [
                'title' => 'Updated Course'
            ])
                ->assertOk()
                ->assertJsonPath('data.title', 'Updated Course');
        });

        it('instructor cannot update other instructor course', function () {
            $otherInstructor = User::factory()->instructor()->create();
            $course = Course::factory()->draft()->create();
            $course->categories()->attach($this->categories->first()->id);
            $course->instructors()->attach($otherInstructor->id);

            putJson("/api/courses/{$course->id}", ['title' => 'Updated Title'])
                ->assertForbidden();
        });

        it('instructor cannot verify course', function () {
            $draftCourse = Course::factory()->draft()->create();
            $draftCourse->instructors()->attach($this->instructor->id);

            postJson("/api/courses/{$draftCourse->id}/verify", [
                'status' => 'published'
            ])
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->user);
        });

        it('student can only see published courses', function () {
            Course::factory()->count(2)->published()->create()->each(function ($course) {
                $course->categories()->attach($this->categories->first()->id);
            });
            Course::factory()->count(1)->draft()->create()->each(function ($course) {
                $course->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/courses')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('student cannot create course', function () {
            postJson('/api/courses', [
                'title' => 'Test Course',
                'category_ids' => [$this->categories->first()->id],
                'level' => 'beginner',
                'lang' => 'english'
            ])
                ->assertForbidden();
        });

        it('student cannot update course', function () {
            $course = Course::factory()->published()->create();

            putJson("/api/courses/{$course->id}", ['title' => 'Updated'])
                ->assertForbidden();
        });
    });
});
