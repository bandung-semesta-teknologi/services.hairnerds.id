<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('course faq crud api', function () {
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

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->create();
        $this->course->categories()->attach($this->categories->first()->id);
    });

    describe('public access', function () {
        it('anyone can get all faqs with pagination without auth', function () {
            CourseFaq::factory()->count(8)->create(['course_id' => $this->course->id]);

            getJson('/api/courses-faqs')
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'course_id',
                            'course',
                            'question',
                            'answer',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('anyone can filter faqs by course without auth', function () {
            $course1 = Course::factory()->create();
            $course2 = Course::factory()->create();

            $course1->categories()->attach($this->categories->first()->id);
            $course2->categories()->attach($this->categories->last()->id);

            CourseFaq::factory()->count(3)->create(['course_id' => $course1->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $course2->id]);

            getJson("/api/courses-faqs?course_id={$course1->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('anyone can get single faq with course relationship without auth', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id)
                ->assertJsonPath('data.question', $faq->question)
                ->assertJsonPath('data.answer', $faq->answer)
                ->assertJsonPath('data.course.id', $this->course->id);
        });

        it('returns 404 when faq not found', function () {
            getJson('/api/courses-faqs/99999')
                ->assertNotFound();
        });

        it('anyone can set custom per_page for pagination', function () {
            CourseFaq::factory()->count(10)->create(['course_id' => $this->course->id]);

            getJson('/api/courses-faqs?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can create new faq', function () {
            $faqData = [
                'course_id' => $this->course->id,
                'question' => 'What is Laravel?',
                'answer' => 'Laravel is a PHP web framework.'
            ];

            postJson('/api/courses-faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('data.question', 'What is Laravel?')
                ->assertJsonPath('data.answer', 'Laravel is a PHP web framework.')
                ->assertJsonPath('data.course_id', $this->course->id);

            $this->assertDatabaseHas('course_faqs', [
                'course_id' => $this->course->id,
                'question' => 'What is Laravel?',
                'answer' => 'Laravel is a PHP web framework.'
            ]);
        });

        it('validates required fields when creating faq', function () {
            postJson('/api/courses-faqs', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['course_id', 'question', 'answer']);
        });

        it('admin can update faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            $updateData = [
                'question' => 'Updated question?',
                'answer' => 'Updated answer.'
            ];

            putJson("/api/courses-faqs/{$faq->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('data.question', 'Updated question?')
                ->assertJsonPath('data.answer', 'Updated answer.');

            $this->assertDatabaseHas('course_faqs', [
                'id' => $faq->id,
                'question' => 'Updated question?',
                'answer' => 'Updated answer.'
            ]);
        });

        it('admin can delete faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJson(['message' => 'FAQ deleted successfully']);

            $this->assertSoftDeleted('course_faqs', ['id' => $faq->id]);
        });

        it('returns 404 when deleting non-existent faq', function () {
            deleteJson('/api/courses-faqs/99999')
                ->assertNotFound();
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can create new faq', function () {
            $faqData = [
                'course_id' => $this->course->id,
                'question' => 'How to start with PHP?',
                'answer' => 'Start with the basics of PHP syntax.'
            ];

            postJson('/api/courses-faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('data.question', 'How to start with PHP?')
                ->assertJsonPath('data.answer', 'Start with the basics of PHP syntax.');
        });

        it('instructor can update faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Instructor updated question?',
                'answer' => 'Instructor updated answer.'
            ])
                ->assertOk()
                ->assertJsonPath('data.question', 'Instructor updated question?');
        });

        it('instructor can delete faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJson(['message' => 'FAQ deleted successfully']);

            $this->assertSoftDeleted('course_faqs', ['id' => $faq->id]);
        });
    });

    describe('student access (forbidden)', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student cannot create faq', function () {
            postJson('/api/courses-faqs', [
                'course_id' => $this->course->id,
                'question' => 'Unauthorized question?',
                'answer' => 'Unauthorized answer.'
            ])
                ->assertForbidden();
        });

        it('student cannot update faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Unauthorized update?'
            ])
                ->assertForbidden();
        });

        it('student cannot delete faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });
    });

    describe('unauthenticated access (forbidden)', function () {
        it('unauthenticated user cannot create faq', function () {
            postJson('/api/courses-faqs', [
                'course_id' => $this->course->id,
                'question' => 'Unauthorized question?',
                'answer' => 'Unauthorized answer.'
            ])
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot update faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Unauthorized update?'
            ])
                ->assertUnauthorized();
        });

        it('unauthenticated user cannot delete faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->course->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertUnauthorized();
        });
    });
});
