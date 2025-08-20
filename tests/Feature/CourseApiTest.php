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

    it('anyone can get all courses with pagination', function () {
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

    it('anyone can filter courses by category', function () {
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

    it('anyone can search courses by title', function () {
        Course::factory()->create(['title' => 'Laravel Advanced Course'])->categories()->attach($this->categories->first()->id);
        Course::factory()->create(['title' => 'Vue.js Basics'])->categories()->attach($this->categories->first()->id);

        getJson('/api/courses?search=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Course');
    });

    it('anyone can filter courses by level', function () {
        Course::factory()->create(['level' => 'beginner'])->categories()->attach($this->categories->first()->id);
        Course::factory()->create(['level' => 'adv'])->categories()->attach($this->categories->first()->id);
        Course::factory()->create(['level' => 'interm'])->categories()->attach($this->categories->first()->id);

        getJson('/api/courses?level=beginner')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.level', 'beginner');
    });

    it('student cannot create course', function () {
        actingAs($this->user);

        $courseData = [
            'title' => 'Test Course',
            'category_ids' => [$this->categories->first()->id],
            'level' => 'beginner',
            'lang' => 'english'
        ];

        postJson('/api/courses', $courseData)
            ->assertForbidden();
    });

    it('admin can create course', function () {
        actingAs(User::factory()->admin()->create());

        $courseData = [
            'title' => 'Test Course',
            'short_description' => 'This is a test course',
            'description' => 'Full description of the test course',
            'requirements' => 'Basic PHP knowledge',
            'category_ids' => [$this->categories->first()->id, $this->categories->last()->id],
            'instructor_ids' => [User::factory()->instructor()->create()->id],
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

    it('instructor can create course', function () {
        actingAs(User::factory()->instructor()->create());

        $courseData = [
            'title' => 'Instructor Course',
            'short_description' => 'This is an instructor course',
            'category_ids' => [$this->categories->first()->id],
            'level' => 'beginner',
            'lang' => 'english',
            'price' => 49
        ];

        postJson('/api/courses', $courseData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Instructor Course')
            ->assertJsonPath('data.slug', 'instructor-course');
    });

    it('instructor can create course with thumbnail', function () {
        actingAs(User::factory()->instructor()->create());
        Storage::fake('public');

        $file = UploadedFile::fake()->image('thumbnail.jpg');

        $courseData = [
            'title' => 'Course with Image',
            'category_ids' => [$this->categories->first()->id],
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
        actingAs(User::factory()->instructor()->create());

        postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'category_ids', 'level', 'lang']);
    });

    it('anyone can get single course with relationships', function () {
        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->take(2)->pluck('id'));

        getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonCount(2, 'data.categories');
    });

    it('returns 404 when course not found', function () {
        getJson('/api/courses/99999')
            ->assertNotFound();
    });

    it('student cannot update course', function () {
        actingAs($this->user);

        $course = Course::factory()->create();

        putJson("/api/courses/{$course->id}", ['title' => 'Updated Title'])
            ->assertForbidden();
    });

    it('admin can update course', function () {
        actingAs(User::factory()->admin()->create());

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

    it('instructor can update course', function () {
        actingAs(User::factory()->instructor()->create());

        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        $updateData = [
            'title' => 'Instructor Updated Course',
            'price' => 199
        ];

        putJson("/api/courses/{$course->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Instructor Updated Course')
            ->assertJsonPath('data.price', 199);
    });

    it('admin can update course categories', function () {
        actingAs(User::factory()->admin()->create());

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

    it('instructor can update course with new thumbnail', function () {
        actingAs(User::factory()->instructor()->create());
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

    it('student cannot delete course', function () {
        actingAs($this->user);

        $course = Course::factory()->create();

        deleteJson("/api/courses/{$course->id}")
            ->assertForbidden();
    });

    it('admin can delete course', function () {
        actingAs(User::factory()->admin()->create());

        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        deleteJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Course deleted successfully');

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    });

    it('instructor can delete course', function () {
        actingAs(User::factory()->instructor()->create());

        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        deleteJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Course deleted successfully');

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    });

    it('returns 404 when deleting non-existent course', function () {
        actingAs(User::factory()->instructor()->create());

        deleteJson('/api/courses/99999')
            ->assertNotFound();
    });

    it('anyone can set custom per_page for pagination', function () {
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

    it('loads all required relationships on index', function () {
        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        getJson('/api/courses')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'categories',
                        'faqs',
                        'sections',
                        'instructors',
                        'reviews'
                    ]
                ]
            ]);
    });

    it('loads all required relationships on show', function () {
        $course = Course::factory()->create();
        $course->categories()->attach($this->categories->first()->id);

        getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'faqs',
                    'sections',
                    'instructors',
                    'reviews'
                ]
            ]);
    });
});
