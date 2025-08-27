<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('review crud api', function () {
    beforeEach(function () {
        $this->admin = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'admin']);

        $this->instructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->otherInstructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->otherStudent = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->categories = Category::factory()->count(2)->create();

        $this->publishedCourse = Course::factory()->published()->create();
        $this->publishedCourse->categories()->attach($this->categories->first()->id);
        $this->publishedCourse->instructors()->attach($this->instructor->id);

        $this->draftCourse = Course::factory()->draft()->create();
        $this->draftCourse->categories()->attach($this->categories->last()->id);
        $this->draftCourse->instructors()->attach($this->instructor->id);

        $this->otherCourse = Course::factory()->published()->create();
        $this->otherCourse->categories()->attach($this->categories->first()->id);
        $this->otherCourse->instructors()->attach($this->otherInstructor->id);

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('guest access', function () {
        it('anyone can get all visible reviews from published courses without auth', function () {
            Review::factory()->count(3)->visible()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->hidden()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->visible()->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/reviews')
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'course_id',
                            'course',
                            'user_id',
                            'user',
                            'comments',
                            'rating',
                            'is_visible',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('anyone can filter reviews by published course without auth', function () {
            Review::factory()->count(3)->visible()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->visible()->create(['course_id' => $this->otherCourse->id]);

            getJson("/api/reviews?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('anyone can get single visible review from published course without auth', function () {
            $review = Review::factory()->visible()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $review->id)
                ->assertJsonPath('data.comments', $review->comments)
                ->assertJsonPath('data.rating', $review->rating);
        });

        it('anyone cannot get hidden review without auth', function () {
            $review = Review::factory()->hidden()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });

        it('anyone cannot get review from draft course without auth', function () {
            $review = Review::factory()->visible()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });

        it('returns 404 when review not found', function () {
            getJson('/api/reviews/99999')
                ->assertNotFound();
        });

        it('anyone can set custom per_page for pagination', function () {
            Review::factory()->count(10)->visible()->create(['course_id' => $this->publishedCourse->id]);

            getJson('/api/reviews?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all reviews including hidden and from draft courses', function () {
            Review::factory()->count(3)->visible()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->hidden()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->visible()->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/reviews')
                ->assertOk()
                ->assertJsonCount(7, 'data');
        });

        it('admin can create new review', function () {
            $reviewData = [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Excellent course!',
                'rating' => 5,
                'is_visible' => true
            ];

            postJson('/api/reviews', $reviewData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Review created successfully')
                ->assertJsonPath('data.comments', 'Excellent course!')
                ->assertJsonPath('data.rating', 5);

            $this->assertDatabaseHas('reviews', [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Excellent course!',
                'rating' => 5
            ]);
        });

        it('validates required fields when creating review', function () {
            postJson('/api/reviews', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['course_id', 'user_id', 'comments', 'rating']);
        });

        it('admin can update any review', function () {
            $review = Review::factory()->create(['course_id' => $this->publishedCourse->id]);

            $updateData = [
                'comments' => 'Updated review comment',
                'rating' => 4,
                'is_visible' => false
            ];

            putJson("/api/reviews/{$review->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Review updated successfully')
                ->assertJsonPath('data.comments', 'Updated review comment')
                ->assertJsonPath('data.rating', 4)
                ->assertJsonPath('data.is_visible', false);

            $this->assertDatabaseHas('reviews', [
                'id' => $review->id,
                'comments' => 'Updated review comment',
                'rating' => 4,
                'is_visible' => false
            ]);
        });

        it('admin can delete any review', function () {
            $review = Review::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Review deleted successfully');

            $this->assertSoftDeleted('reviews', ['id' => $review->id]);
        });

        it('admin can view any review', function () {
            $review = Review::factory()->hidden()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $review->id);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see reviews only from their own courses', function () {
            Review::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);
            Review::factory()->count(2)->create(['course_id' => $this->otherCourse->id]);

            getJson('/api/reviews')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can view review from their own course', function () {
            $review = Review::factory()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $review->id);
        });

        it('instructor cannot view review from other instructor course', function () {
            $review = Review::factory()->create(['course_id' => $this->otherCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });

        it('instructor cannot create review', function () {
            postJson('/api/reviews', [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Great course!',
                'rating' => 5
            ])
                ->assertForbidden();
        });

        it('instructor cannot update review', function () {
            $review = Review::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/reviews/{$review->id}", [
                'comments' => 'Updated comment'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete review', function () {
            $review = Review::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view visible reviews from published courses only', function () {
            Review::factory()->count(3)->visible()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->hidden()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->visible()->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/reviews')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view single visible review from published course', function () {
            $review = Review::factory()->visible()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $review->id);
        });

        it('student cannot view hidden review', function () {
            $review = Review::factory()->hidden()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });

        it('student can create review for enrolled course', function () {
            $reviewData = [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Great learning experience!',
                'rating' => 5
            ];

            postJson('/api/reviews', $reviewData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Review created successfully')
                ->assertJsonPath('data.comments', 'Great learning experience!')
                ->assertJsonPath('data.rating', 5);

            $this->assertDatabaseHas('reviews', [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Great learning experience!',
                'rating' => 5
            ]);
        });

        it('student cannot create review for non-enrolled course', function () {
            postJson('/api/reviews', [
                'course_id' => $this->otherCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Good course!',
                'rating' => 4
            ])
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'You can only review courses you are enrolled in');
        });

        it('student cannot create duplicate review for same course', function () {
            Review::factory()->create([
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id
            ]);

            postJson('/api/reviews', [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Another review',
                'rating' => 3
            ])
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'You have already reviewed this course');
        });

        it('student can update their own review', function () {
            $review = Review::factory()->create([
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id
            ]);

            putJson("/api/reviews/{$review->id}", [
                'comments' => 'Updated my review',
                'rating' => 4
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Review updated successfully')
                ->assertJsonPath('data.comments', 'Updated my review')
                ->assertJsonPath('data.rating', 4);
        });

        it('student cannot update other student review', function () {
            $review = Review::factory()->create([
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->otherStudent->id
            ]);

            putJson("/api/reviews/{$review->id}", [
                'comments' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('student can delete their own review', function () {
            $review = Review::factory()->create([
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id
            ]);

            deleteJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Review deleted successfully');

            $this->assertSoftDeleted('reviews', ['id' => $review->id]);
        });

        it('student cannot delete other student review', function () {
            $review = Review::factory()->create([
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->otherStudent->id
            ]);

            deleteJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });
    });

    describe('guest access', function () {
        it('guest user can view visible reviews from published courses', function () {
            Review::factory()->count(3)->visible()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->hidden()->create(['course_id' => $this->publishedCourse->id]);
            Review::factory()->count(2)->visible()->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/reviews')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('guest user can view single visible review from published course', function () {
            $review = Review::factory()->visible()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $review->id);
        });

        it('guest user cannot view hidden review', function () {
            $review = Review::factory()->hidden()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/reviews/{$review->id}")
                ->assertForbidden();
        });

        it('guest user cannot create review', function () {
            postJson('/api/reviews', [
                'course_id' => $this->publishedCourse->id,
                'user_id' => $this->student->id,
                'comments' => 'Good course!',
                'rating' => 4
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update review', function () {
            $review = Review::factory()->visible()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/reviews/{$review->id}", [
                'comments' => 'Unauthorized update'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete review', function () {
            $review = Review::factory()->visible()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/reviews/{$review->id}")
                ->assertUnauthorized();
        });
    });
});
