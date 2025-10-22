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
        $this->admin = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'admin']);

        $this->instructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);
    });

    describe('guest access', function () {
        it('anyone can get all categories without auth', function () {
            Category::factory()->count(8)->create();

            getJson('/api/academy/categories')
                ->assertOk()
                ->assertJsonCount(8, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
        });

        it('anyone can get single category without auth', function () {
            $category = Category::factory()->create();

            getJson("/api/academy/categories/{$category->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $category->id)
                ->assertJsonPath('data.name', $category->name);
        });

        it('returns 404 when category not found', function () {
            getJson('/api/academy/categories/99999')
                ->assertNotFound();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can create new category', function () {
            $categoryData = [
                'name' => 'Web Development'
            ];

            postJson('/api/academy/categories', $categoryData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Category created successfully')
                ->assertJsonPath('data.name', 'Web Development');

            $this->assertDatabaseHas('categories', [
                'name' => 'Web Development'
            ]);
        });

        it('validates name is required when creating category', function () {
            postJson('/api/academy/categories', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        it('admin can update category', function () {
            $category = Category::factory()->create(['name' => 'Old Name']);

            putJson("/api/academy/categories/{$category->id}", [
                'name' => 'Updated Name'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Category updated successfully')
                ->assertJsonPath('data.name', 'Updated Name');

            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => 'Updated Name'
            ]);
        });

        it('admin can delete category without courses', function () {
            $category = Category::factory()->create();

            deleteJson("/api/academy/categories/{$category->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Category deleted successfully');

            $this->assertSoftDeleted('categories', ['id' => $category->id]);
        });

        it('admin cannot delete category with existing courses', function () {
            $category = Category::factory()->create();
            $course = Course::factory()->create();
            $course->categories()->attach($category->id);

            deleteJson("/api/academy/categories/{$category->id}")
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Cannot delete category with existing courses');

            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'deleted_at' => null
            ]);
        });

        it('returns 404 when deleting non-existent category', function () {
            deleteJson('/api/academy/categories/99999')
                ->assertNotFound();
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can create new category', function () {
            $categoryData = [
                'name' => 'Mobile Development'
            ];

            postJson('/api/academy/categories', $categoryData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Category created successfully')
                ->assertJsonPath('data.name', 'Mobile Development');

            $this->assertDatabaseHas('categories', [
                'name' => 'Mobile Development'
            ]);
        });

        it('instructor can update category', function () {
            $category = Category::factory()->create(['name' => 'Old Name']);

            putJson("/api/academy/categories/{$category->id}", [
                'name' => 'Instructor Updated'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Category updated successfully')
                ->assertJsonPath('data.name', 'Instructor Updated');
        });

        it('instructor can delete category', function () {
            $category = Category::factory()->create();

            deleteJson("/api/academy/categories/{$category->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Category deleted successfully');

            $this->assertSoftDeleted('categories', ['id' => $category->id]);
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view all categories', function () {
            Category::factory()->count(5)->create();

            getJson('/api/academy/categories')
                ->assertOk()
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
        });

        it('student can view single category', function () {
            $category = Category::factory()->create();

            getJson("/api/academy/categories/{$category->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $category->id)
                ->assertJsonPath('data.name', $category->name);
        });

        it('student cannot create category', function () {
            postJson('/api/academy/categories', [
                'name' => 'Unauthorized Category'
            ])
                ->assertForbidden();
        });

        it('student cannot update category', function () {
            $category = Category::factory()->create();

            putJson("/api/academy/categories/{$category->id}", [
                'name' => 'Unauthorized Update'
            ])
                ->assertForbidden();
        });

        it('student cannot delete category', function () {
            $category = Category::factory()->create();

            deleteJson("/api/academy/categories/{$category->id}")
                ->assertForbidden();
        });
    });

    describe('guest access', function () {
        it('guest user can view all categories', function () {
            Category::factory()->count(3)->create();

            getJson('/api/academy/categories')
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
        });

        it('guest user can view single category', function () {
            $category = Category::factory()->create();

            getJson("/api/academy/categories/{$category->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $category->id)
                ->assertJsonPath('data.name', $category->name);
        });

        it('guest user cannot create category', function () {
            postJson('/api/academy/categories', [
                'name' => 'Unauthorized Category'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update category', function () {
            $category = Category::factory()->create();

            putJson("/api/academy/categories/{$category->id}", [
                'name' => 'Unauthorized Update'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete category', function () {
            $category = Category::factory()->create();

            deleteJson("/api/academy/categories/{$category->id}")
                ->assertUnauthorized();
        });
    });
});
