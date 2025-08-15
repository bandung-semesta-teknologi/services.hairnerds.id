<?php

use App\Models\Course;
use App\Models\CourseCategory;
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

        $this->category = CourseCategory::factory()->create();
    });

    it('user can get all courses with pagination', function () {
        Course::factory()->count(10)->create(['category_id' => $this->category->id]);

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
                        'what_will_learn',
                        'requirements',
                        'category_id',
                        'category',
                        'level',
                        'language',
                        'enable_drip_content',
                        'price',
                        'thumbnail',
                        'status',
                        'faqs',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('user can filter courses by category', function () {
        $category1 = CourseCategory::factory()->create();
        $category2 = CourseCategory::factory()->create();

        Course::factory()->count(3)->create(['category_id' => $category1->id]);
        Course::factory()->count(2)->create(['category_id' => $category2->id]);

        getJson("/api/courses?category_id={$category1->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can search courses by title', function () {
        Course::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Laravel Advanced Course'
        ]);
        Course::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Vue.js Basics'
        ]);

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
            'what_will_learn' => 'You will learn testing',
            'requirements' => 'Basic PHP knowledge',
            'category_id' => $this->category->id,
            'level' => 'intermediate',
            'language' => 'english',
            'enable_drip_content' => true,
            'price' => 99.99,
            'status' => 'draft'
        ];

        postJson('/api/courses', $courseData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Test Course')
            ->assertJsonPath('data.slug', 'test-course')
            ->assertJsonPath('data.category.id', $this->category->id);

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
            'category_id' => $this->category->id,
            'level' => 'beginner',
            'thumbnail' => $file
        ];

        $response = postJson('/api/courses', $courseData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Course with Image');

        $thumbnailPath = $response->json('data.thumbnail');

        expect($thumbnailPath)->not()->toBeNull();
        Storage::disk('public')->assertExists($thumbnailPath);
    });

    it('generates unique slug when title already exists', function () {
        Course::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Duplicate Title',
            'slug' => 'duplicate-title'
        ]);

        postJson('/api/courses', [
            'title' => 'Duplicate Title',
            'category_id' => $this->category->id,
            'level' => 'beginner'
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'duplicate-title-1');
    });

    it('validates required fields when creating course', function () {
        postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'category_id', 'level']);
    });

    it('user can get single course with relationships', function () {
        $course = Course::factory()
            ->for($this->category, 'category')
            ->hasFaqs(3)
            ->create();

        getJson("/api/courses/{$course->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.category.id', $this->category->id)
            ->assertJsonCount(3, 'data.faqs');
    });

    it('returns 404 when course not found', function () {
        getJson('/api/courses/non-existent-slug')
            ->assertNotFound();
    });

    it('user can update course', function () {
        $course = Course::factory()->create(['category_id' => $this->category->id]);

        $updateData = [
            'title' => 'Updated Course Title',
            'short_description' => 'Updated description',
            'price' => 149.99
        ];

        putJson("/api/courses/{$course->slug}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Course Title')
            ->assertJsonPath('data.slug', 'updated-course-title')
            ->assertJsonPath('data.price', '149.99');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Course Title',
            'slug' => 'updated-course-title'
        ]);
    });

    it('user can update course with new thumbnail', function () {
        Storage::fake('public');

        $course = Course::factory()->create(['category_id' => $this->category->id]);

        $file = UploadedFile::fake()->image('new-thumbnail.jpg');

        $updateData = [
            'title' => 'Course with New Image',
            'thumbnail' => $file
        ];

        $response = putJson("/api/courses/{$course->slug}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Course with New Image');

        $thumbnailPath = $response->json('data.thumbnail');

        expect($thumbnailPath)->not()->toBeNull();
        Storage::disk('public')->assertExists($thumbnailPath);
    });

    it('updates slug when title changes', function () {
        $course = Course::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Original Title',
            'slug' => 'original-title'
        ]);

        putJson("/api/courses/{$course->slug}", [
            'title' => 'New Title'
        ])
            ->assertOk()
            ->assertJsonPath('data.slug', 'new-title');
    });

    it('user can partially update course', function () {
        $course = Course::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Original Title',
            'price' => 99.99
        ]);

        putJson("/api/courses/{$course->slug}", [
            'price' => 199.99
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Original Title')
            ->assertJsonPath('data.price', '199.99');
    });

    it('validates fields when updating course', function () {
        $course = Course::factory()->create(['category_id' => $this->category->id]);

        putJson("/api/courses/{$course->slug}", [
            'level' => 'invalid_level',
            'category_id' => 99999
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level', 'category_id']);
    });

    it('user can delete course', function () {
        $course = Course::factory()->create(['category_id' => $this->category->id]);

        deleteJson("/api/courses/{$course->slug}")
            ->assertOk()
            ->assertJson(['message' => 'Course deleted successfully']);

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    });

    it('returns 404 when deleting non-existent course', function () {
        deleteJson('/api/courses/non-existent-slug')
            ->assertNotFound();
    });

    it('user can set custom per_page for pagination', function () {
        Course::factory()->count(10)->create(['category_id' => $this->category->id]);

        getJson('/api/courses?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('orders courses by latest first', function () {
        $older = Course::factory()->create([
            'category_id' => $this->category->id,
            'created_at' => now()->subDay()
        ]);

        $newer = Course::factory()->create([
            'category_id' => $this->category->id,
            'created_at' => now()
        ]);

        getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });
});
