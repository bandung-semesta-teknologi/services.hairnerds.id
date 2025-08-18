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

        actingAs($this->user);

        $this->categories = Category::factory()->count(3)->create();
    });

    it('user can get all courses with pagination', function () {
        Course::factory()->count(10)->create()->each(function ($course) {
            $course->categories()->attach($this->categories->random(2)->pluck('id'));
        });

        getJson('/api/courses')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'short_description',
                        'description',
                        'requirements',
                        'categories',
                        'level',
                        'lang',
                        'price',
                        'thumbnail',
                        'verified_at',
                        'faqs',
                        'sections',
                        'instructors',
                        'reviews',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('user can filter courses by category', function () {
        $category1 = $this->categories->first();
        $category2 = $this->categories->last();

        $courses1 = Course::factory()->count(3)->create();
        $courses2 = Course::factory()->count(2)->create();

        $courses1->each(fn($course) => $course->categories()->attach($category1->id));
        $courses2->each(fn($course) => $course->categories()->attach($category2->id));

        getJson("/api/courses?category_id={$category1->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can search courses by title', function () {
        Course::factory()->create(['title' => 'Laravel Advanced Course'])->categories()->attach($this->categories->first()->id);
        Course::factory()->create(['title' => 'Vue.js Basics'])->categories()->attach($this->categories->first()->id);

        getJson('/api/courses?search=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Course');
    });

    it('user can create course', function () {
        $courseData = [
            'title' => 'Test Course',
            'short_description' => 'This is a test course',
            'description' => 'Full description of the test course',
            'requirements' => 'Basic PHP knowledge',
            'category_ids' => [$this->categories->first()->id, $this->categories->last()->id],
            'instructor_ids' => [User::factory()->create()->id],
            'level' => 'interm',
            'lang' => 'english',
            'price' => 99,
            'verified_at' => now()->toDateString()
        ];

        postJson('/api/courses', $courseData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Test Course')
            ->assertJsonPath('data.slug', 'test-course')
            ->assertJsonCount(2, 'data.categories')
            ->assertJsonCount(1, 'data.instructors');

        $this->assertDatabaseHas('courses', [
            'title' => 'Test Course',
            'slug' => 'test-course'
        ]);
    });

    it('user can create course with thumbnail', function () {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('thumbnail.jpg');

        $courseData = [
            'title' => 'Course with Image',
            'category_ids' => [$this->categories->first()->id],
            'instructor_ids' => [User::factory()->create()->id],
            'level' => 'beginner',
            'lang' => 'english',
            'thumbnail' => $file
        ];

        $response = postJson('/api/courses', $courseData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Course with Image');

        $thumbnailPath = $response->json('data.thumbnail');

        expect($thumbnailPath)->not()->toBeNull();
        Storage::disk('public')->assertExists($thumbnailPath);
    });

    it('validates required fields when creating course', function () {
        postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'category_ids', 'level', 'lang']);
    });

    it('user can get single course with relationships', function () {
        $course = Course::factory()->hasFaqs(3)->hasSections(5)->create();
        $course->categories()->attach($this->categories->take(2)->pluck('id'));

        getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonCount(2, 'data.categories')
            ->assertJsonCount(3, 'data.faqs')
            ->assertJsonCount(5, 'data.sections');
    });

    it('returns 404 when course not found', function () {
        getJson('/api/courses/99999')
            ->assertNotFound();
    });

    it('user can update course', function () {
        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        $updateData = [
            'title' => 'Updated Course Title',
            'short_description' => 'Updated description',
            'description' => 'Updated full description',
            'price' => 149
        ];

        putJson("/api/courses/{$course->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Course Title')
            ->assertJsonPath('data.slug', 'updated-course-title')
            ->assertJsonPath('data.price', 149);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Course Title',
            'slug' => 'updated-course-title'
        ]);
    });

    it('user can update course categories', function () {
        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        $updateData = [
            'category_ids' => $this->categories->take(2)->pluck('id')->toArray()
        ];

        putJson("/api/courses/{$course->id}", $updateData)
            ->assertOk()
            ->assertJsonCount(2, 'data.categories');

        expect($course->fresh()->categories()->count())->toBe(2);
    });

    it('user can update course with new thumbnail', function () {
        Storage::fake('public');

        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        $file = UploadedFile::fake()->image('new-thumbnail.jpg');

        $updateData = [
            'title' => 'Course with New Image',
            'thumbnail' => $file
        ];

        $response = putJson("/api/courses/{$course->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Course with New Image');

        $thumbnailPath = $response->json('data.thumbnail');

        expect($thumbnailPath)->not()->toBeNull();
        Storage::disk('public')->assertExists($thumbnailPath);
    });

    it('user can delete course', function () {
        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        deleteJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJson(['message' => 'Course deleted successfully']);

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    });

    it('returns 404 when deleting non-existent course', function () {
        deleteJson('/api/courses/99999')
            ->assertNotFound();
    });

    it('user can set custom per_page for pagination', function () {
        Course::factory()->count(10)->create()->each(function ($course) {
            $course->categories()->attach($this->categories->random()->id);
        });

        getJson('/api/courses?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('orders courses by latest first', function () {
        $older = Course::factory()->create(['created_at' => now()->subDay()]);
        $newer = Course::factory()->create(['created_at' => now()]);

        $older->categories()->attach($this->categories->first()->id);
        $newer->categories()->attach($this->categories->first()->id);

        getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });
});
