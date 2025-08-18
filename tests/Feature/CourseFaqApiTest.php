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
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        actingAs($this->user);

        $this->categories = Category::factory()->count(2)->create();
        $this->course = Course::factory()->create();
        $this->course->categories()->attach($this->categories->first()->id);
    });

    it('user can get all faqs with pagination', function () {
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

    it('user can filter faqs by course', function () {
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

    it('user can create new faq', function () {
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

    it('user can get single faq with course relationship', function () {
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

    it('user can update faq', function () {
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

    it('user can delete faq', function () {
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

    it('user can set custom per_page for pagination', function () {
        CourseFaq::factory()->count(10)->create(['course_id' => $this->course->id]);

        getJson('/api/courses-faqs?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });
});
