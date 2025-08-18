<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('category crud api', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);
    });

    it('user can get all categories with pagination', function () {
        Category::factory()->count(8)->create();

        getJson('/api/categories')
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

        postJson('/api/categories', $categoryData)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Web Development');

        $this->assertDatabaseHas('categories', [
            'name' => 'Web Development'
        ]);
    });

    it('validates name is required when creating category', function () {
        postJson('/api/categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('user can get single category', function () {
        $category = Category::factory()->create();

        getJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    });

    it('returns 404 when category not found', function () {
        getJson('/api/categories/99999')
            ->assertNotFound();
    });

    it('user can update category', function () {
        $category = Category::factory()->create(['name' => 'Old Name']);

        putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name'
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name'
        ]);
    });

    it('user can delete category without courses', function () {
        $category = Category::factory()->create();

        deleteJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJson(['message' => 'Category deleted successfully']);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    });

    it('cannot delete category with existing courses', function () {
        $category = Category::factory()->create();
        $course = Course::factory()->create();
        $course->categories()->attach($category->id);

        deleteJson("/api/categories/{$category->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Cannot delete category with existing courses']);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null
        ]);
    });

    it('returns 404 when deleting non-existent category', function () {
        deleteJson('/api/categories/99999')
            ->assertNotFound();
    });

    it('user can set custom per_page for pagination', function () {
        Category::factory()->count(10)->create();

        getJson('/api/categories?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
});
