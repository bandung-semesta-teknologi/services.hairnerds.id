<?php

use App\Models\Category;
use App\Models\Course;
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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->create();
        $this->course->categories()->attach($this->categories->first()->id);
        $this->reviewer = User::factory()->create();
    });

    it('user can get all reviews with pagination', function () {
        Review::factory()->count(8)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);

        getJson('/api/reviews')
            ->assertOk()
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

    it('user can filter reviews by course', function () {
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        $course1->categories()->attach($this->categories->first()->id);
        $course2->categories()->attach($this->categories->last()->id);

        Review::factory()->count(3)->create([
            'course_id' => $course1->id,
            'user_id' => $this->reviewer->id
        ]);
        Review::factory()->count(2)->create([
            'course_id' => $course2->id,
            'user_id' => $this->reviewer->id
        ]);

        getJson("/api/reviews?course_id={$course1->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter reviews by user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Review::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'user_id' => $user1->id
        ]);
        Review::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'user_id' => $user2->id
        ]);

        getJson("/api/reviews?user_id={$user1->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can filter reviews by rating', function () {
        Review::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'rating' => 5
        ]);
        Review::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4
        ]);

        getJson('/api/reviews?rating=5')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('user can filter reviews by visibility', function () {
        Review::factory()->visible()->count(2)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);
        Review::factory()->hidden()->count(3)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);

        getJson('/api/reviews?is_visible=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        getJson('/api/reviews?is_visible=0')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('user can create new review', function () {
        $reviewData = [
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'comments' => 'Great course! Very informative.',
            'rating' => 5,
            'is_visible' => true
        ];

        postJson('/api/reviews', $reviewData)
            ->assertCreated()
            ->assertJsonPath('data.comments', 'Great course! Very informative.')
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.is_visible', true)
            ->assertJsonPath('data.course_id', $this->course->id)
            ->assertJsonPath('data.user_id', $this->reviewer->id);

        $this->assertDatabaseHas('reviews', [
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'comments' => 'Great course! Very informative.',
            'rating' => 5
        ]);
    });

    it('validates required fields when creating review', function () {
        postJson('/api/reviews', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['course_id', 'user_id', 'comments', 'rating']);
    });

    it('validates rating range when creating review', function () {
        postJson('/api/reviews', [
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'comments' => 'Test comment',
            'rating' => 6
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        postJson('/api/reviews', [
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'comments' => 'Test comment',
            'rating' => 0
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    });

    it('user can get single review with relationships', function () {
        $review = Review::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);

        getJson("/api/reviews/{$review->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.comments', $review->comments)
            ->assertJsonPath('data.rating', $review->rating)
            ->assertJsonPath('data.course.id', $this->course->id)
            ->assertJsonPath('data.course.title', $this->course->title)
            ->assertJsonPath('data.user.name', $this->reviewer->name);
    });

    it('returns 404 when review not found', function () {
        getJson('/api/reviews/99999')
            ->assertNotFound();
    });

    it('user can update review', function () {
        $review = Review::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);

        $updateData = [
            'comments' => 'Updated review comment',
            'rating' => 4,
            'is_visible' => false
        ];

        putJson("/api/reviews/{$review->id}", $updateData)
            ->assertOk()
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

    it('user can delete review', function () {
        $review = Review::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);

        deleteJson("/api/reviews/{$review->id}")
            ->assertOk()
            ->assertJson(['message' => 'Review deleted successfully']);

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    });

    it('returns 404 when deleting non-existent review', function () {
        deleteJson('/api/reviews/99999')
            ->assertNotFound();
    });

    it('user can set custom per_page for pagination', function () {
        Review::factory()->count(10)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id
        ]);

        getJson('/api/reviews?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('reviews are ordered by latest first', function () {
        $older = Review::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'created_at' => now()->subDay()
        ]);
        $newer = Review::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->reviewer->id,
            'created_at' => now()
        ]);

        getJson('/api/reviews')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });
});
