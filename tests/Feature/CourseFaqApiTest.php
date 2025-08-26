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

        $this->otherInstructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
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
    });

    describe('public access', function () {
        it('anyone can get all faqs from published courses only without auth', function () {
            CourseFaq::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/courses-faqs')
                ->assertOk()
                ->assertJsonCount(3, 'data')
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

        it('anyone can filter faqs by published course without auth', function () {
            CourseFaq::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('anyone cannot access faqs from draft course without auth', function () {
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs?course_id={$this->draftCourse->id}")
                ->assertOk()
                ->assertJsonCount(0, 'data');
        });

        it('anyone can get single faq from published course without auth', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id)
                ->assertJsonPath('data.question', $faq->question)
                ->assertJsonPath('data.answer', $faq->answer)
                ->assertJsonPath('data.course.id', $this->publishedCourse->id);
        });

        it('anyone cannot get single faq from draft course without auth', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('returns 404 when faq not found', function () {
            getJson('/api/courses-faqs/99999')
                ->assertNotFound();
        });

        it('anyone can set custom per_page for pagination', function () {
            CourseFaq::factory()->count(10)->create(['course_id' => $this->publishedCourse->id]);

            getJson('/api/courses-faqs?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all faqs including draft courses', function () {
            CourseFaq::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/courses-faqs')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('admin can create new faq', function () {
            $faqData = [
                'course_id' => $this->publishedCourse->id,
                'question' => 'What is Laravel?',
                'answer' => 'Laravel is a PHP web framework.'
            ];

            postJson('/api/courses-faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ created successfully')
                ->assertJsonPath('data.question', 'What is Laravel?')
                ->assertJsonPath('data.answer', 'Laravel is a PHP web framework.')
                ->assertJsonPath('data.course_id', $this->publishedCourse->id);

            $this->assertDatabaseHas('course_faqs', [
                'course_id' => $this->publishedCourse->id,
                'question' => 'What is Laravel?',
                'answer' => 'Laravel is a PHP web framework.'
            ]);
        });

        it('validates required fields when creating faq', function () {
            postJson('/api/courses-faqs', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['course_id', 'question', 'answer']);
        });

        it('admin can update any faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            $updateData = [
                'question' => 'Updated question?',
                'answer' => 'Updated answer.'
            ];

            putJson("/api/courses-faqs/{$faq->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ updated successfully')
                ->assertJsonPath('data.question', 'Updated question?')
                ->assertJsonPath('data.answer', 'Updated answer.');

            $this->assertDatabaseHas('course_faqs', [
                'id' => $faq->id,
                'question' => 'Updated question?',
                'answer' => 'Updated answer.'
            ]);
        });

        it('admin can delete any faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ deleted successfully');

            $this->assertSoftDeleted('course_faqs', ['id' => $faq->id]);
        });

        it('admin can view faq from any course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id);
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

        it('instructor can see faqs only from their own courses', function () {
            CourseFaq::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->otherCourse->id]);

            getJson('/api/courses-faqs')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can create faq for their own course', function () {
            $faqData = [
                'course_id' => $this->publishedCourse->id,
                'question' => 'How to start with PHP?',
                'answer' => 'Start with the basics of PHP syntax.'
            ];

            postJson('/api/courses-faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ created successfully')
                ->assertJsonPath('data.question', 'How to start with PHP?')
                ->assertJsonPath('data.answer', 'Start with the basics of PHP syntax.');
        });

        it('instructor can update faq from their own course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Instructor updated question?',
                'answer' => 'Instructor updated answer.'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ updated successfully')
                ->assertJsonPath('data.question', 'Instructor updated question?');
        });

        it('instructor can delete faq from their own course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ deleted successfully');

            $this->assertSoftDeleted('course_faqs', ['id' => $faq->id]);
        });

        it('instructor can view faq from their own course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id);
        });

        it('instructor cannot update faq from other instructor course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->otherCourse->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Unauthorized update?'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete faq from other instructor course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->otherCourse->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('instructor cannot view faq from other instructor course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->otherCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view faqs from published courses only', function () {
            CourseFaq::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/courses-faqs')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view single faq from published course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id);
        });

        it('student cannot view faq from draft course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('student cannot create faq', function () {
            postJson('/api/courses-faqs', [
                'course_id' => $this->publishedCourse->id,
                'question' => 'Unauthorized question?',
                'answer' => 'Unauthorized answer.'
            ])
                ->assertForbidden();
        });

        it('student cannot update faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Unauthorized update?'
            ])
                ->assertForbidden();
        });

        it('student cannot delete faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });
    });

    describe('guest access', function () {
        it('guest user can view faqs from published courses', function () {
            CourseFaq::factory()->count(3)->create(['course_id' => $this->publishedCourse->id]);
            CourseFaq::factory()->count(2)->create(['course_id' => $this->draftCourse->id]);

            getJson('/api/courses-faqs')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('guest user can view single faq from published course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id);
        });

        it('guest user cannot view faq from draft course', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->draftCourse->id]);

            getJson("/api/courses-faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('guest user cannot create faq', function () {
            postJson('/api/courses-faqs', [
                'course_id' => $this->publishedCourse->id,
                'question' => 'Unauthorized question?',
                'answer' => 'Unauthorized answer.'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            putJson("/api/courses-faqs/{$faq->id}", [
                'question' => 'Unauthorized update?'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete faq', function () {
            $faq = CourseFaq::factory()->create(['course_id' => $this->publishedCourse->id]);

            deleteJson("/api/courses-faqs/{$faq->id}")
                ->assertUnauthorized();
        });
    });
});
