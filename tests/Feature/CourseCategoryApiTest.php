<?php

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('course category crud api', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);
    });

    it('user can get all categories with pagination', function () {
        CourseCategory::factory()->count(8)->create();

        getJson('/api/course-categories')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('user can create new category', function () {
        $categoryData = [
            'name' => 'Web Development'
        ];

        postJson('/api/course-categories', $categoryData)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Web Development');

        $this->assertDatabaseHas('course_categories', [
            'name' => 'Web Development'
        ]);
    });

    it('validates name is required when creating category', function () {
        postJson('/api/course-categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('user can get single category', function () {
        $category = CourseCategory::factory()->create();

        getJson("/api/course-categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    });

    it('returns 404 when category not found', function () {
        getJson('/api/course-categories/99999')
            ->assertNotFound();
    });

    it('user can update category', function () {
        $category = CourseCategory::factory()->create(['name' => 'Old Name']);

        putJson("/api/course-categories/{$category->id}", [
            'name' => 'Updated Name'
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('course_categories', [
            'id' => $category->id,
            'name' => 'Updated Name'
        ]);
    });

    it('user can delete category without courses', function () {
        $category = CourseCategory::factory()->create();

        deleteJson("/api/course-categories/{$category->id}")
            ->assertOk()
            ->assertJson(['message' => 'Course category deleted successfully']);

        $this->assertSoftDeleted('course_categories', ['id' => $category->id]);
    });

    it('cannot delete category with existing courses', function () {
        $category = CourseCategory::factory()
            ->has(Course::factory()->count(2))
            ->create();

        deleteJson("/api/course-categories/{$category->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Cannot delete category with existing courses']);

        $this->assertDatabaseHas('course_categories', [
            'id' => $category->id,
            'deleted_at' => null
        ]);
    });

    it('returns 404 when deleting non-existent category', function () {
        deleteJson('/api/course-categories/99999')
            ->assertNotFound();
    });

    it('user can set custom per_page for pagination', function () {
        CourseCategory::factory()->count(10)->create();

        getJson('/api/course-categories?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
});
