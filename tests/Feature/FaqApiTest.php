<?php

use App\Models\Bootcamp;
use App\Models\Category;
use App\Models\Course;
use App\Models\Faq;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('faq crud api', function () {
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

        $this->publishedBootcamp = Bootcamp::factory()->published()->create([
            'user_id' => $this->instructor->id
        ]);
        $this->publishedBootcamp->categories()->attach($this->categories->first()->id);

        $this->draftBootcamp = Bootcamp::factory()->draft()->create([
            'user_id' => $this->instructor->id
        ]);
        $this->draftBootcamp->categories()->attach($this->categories->last()->id);

        $this->otherBootcamp = Bootcamp::factory()->published()->create([
            'user_id' => $this->otherInstructor->id
        ]);
        $this->otherBootcamp->categories()->attach($this->categories->first()->id);
    });

    describe('guest access', function () {
        it('anyone can get all faqs from published courses and bootcamps only without auth', function () {
            Faq::factory()->count(3)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->draftCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);
            Faq::factory()->count(1)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->draftBootcamp->id
            ]);

            getJson('/api/faqs')
                ->assertOk()
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'faqable_type',
                            'faqable_id',
                            'question',
                            'answer',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
        });

        it('anyone can filter faqs by faqable type', function () {
            Faq::factory()->count(3)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);

            getJson('/api/faqs?faqable_type=App\\Models\\Course')
                ->assertOk()
                ->assertJsonCount(3, 'data');

            getJson('/api/faqs?faqable_type=App\\Models\\Bootcamp')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('anyone can get single faq from published course without auth', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            getJson("/api/faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id)
                ->assertJsonPath('data.question', $faq->question)
                ->assertJsonPath('data.answer', $faq->answer);
        });

        it('anyone can get single faq from published bootcamp without auth', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);

            getJson("/api/faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id);
        });

        it('anyone cannot get single faq from draft course without auth', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->draftCourse->id
            ]);

            getJson("/api/faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('anyone cannot get single faq from draft bootcamp without auth', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->draftBootcamp->id
            ]);

            getJson("/api/faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('guest user cannot create faq', function () {
            postJson('/api/faqs', [
                'faqable_type' => 'App\\Models\\Course',
                'faqable_id' => $this->publishedCourse->id,
                'faqs' => [
                    [
                        'question' => 'Unauthorized question?',
                        'answer' => 'Unauthorized answer.'
                    ]
                ]
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update faq', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            putJson("/api/faqs/{$faq->id}", [
                'question' => 'Unauthorized update?'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete faq', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertUnauthorized();
        });

        it('returns 404 when faq not found', function () {
            getJson('/api/faqs/99999')
                ->assertNotFound();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all faqs including draft courses and bootcamps', function () {
            Faq::factory()->count(3)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->draftCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);
            Faq::factory()->count(1)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->draftBootcamp->id
            ]);

            getJson('/api/faqs')
                ->assertOk()
                ->assertJsonCount(8, 'data');
        });

        it('admin can create multiple faqs for course at once', function () {
            $faqData = [
                'faqable_type' => 'App\\Models\\Course',
                'faqable_id' => $this->publishedCourse->id,
                'faqs' => [
                    [
                        'question' => 'What is Laravel?',
                        'answer' => 'Laravel is a PHP web framework.'
                    ],
                    [
                        'question' => 'What is PHP?',
                        'answer' => 'PHP is a server-side scripting language.'
                    ]
                ]
            ];

            postJson('/api/faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQs created successfully')
                ->assertJsonCount(2, 'data');

            $this->assertDatabaseHas('faqs', [
                'faqable_type' => 'App\\Models\\Course',
                'faqable_id' => $this->publishedCourse->id,
                'question' => 'What is Laravel?',
                'answer' => 'Laravel is a PHP web framework.'
            ]);
        });

        it('admin can create multiple faqs for bootcamp at once', function () {
            $faqData = [
                'faqable_type' => 'App\\Models\\Bootcamp',
                'faqable_id' => $this->publishedBootcamp->id,
                'faqs' => [
                    [
                        'question' => 'What is included?',
                        'answer' => 'All materials and lunch.'
                    ],
                    [
                        'question' => 'Where is the location?',
                        'answer' => 'Jakarta Convention Center.'
                    ]
                ]
            ];

            postJson('/api/faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonCount(2, 'data');

            $this->assertDatabaseHas('faqs', [
                'faqable_type' => 'App\\Models\\Bootcamp',
                'faqable_id' => $this->publishedBootcamp->id,
                'question' => 'What is included?'
            ]);
        });

        it('validates required fields when creating faqs', function () {
            postJson('/api/faqs', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['faqable_type', 'faqable_id', 'faqs']);
        });

        it('validates faqs array must not be empty', function () {
            postJson('/api/faqs', [
                'faqable_type' => 'App\\Models\\Course',
                'faqable_id' => $this->publishedCourse->id,
                'faqs' => []
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['faqs']);
        });

        it('validates faqable_type must be valid', function () {
            postJson('/api/faqs', [
                'faqable_type' => 'App\\Models\\InvalidModel',
                'faqable_id' => 1,
                'faqs' => [
                    [
                        'question' => 'Test?',
                        'answer' => 'Test answer.'
                    ]
                ]
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['faqable_type']);
        });

        it('admin can update any faq', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            $updateData = [
                'question' => 'Updated question?',
                'answer' => 'Updated answer.'
            ];

            putJson("/api/faqs/{$faq->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ updated successfully')
                ->assertJsonPath('data.question', 'Updated question?')
                ->assertJsonPath('data.answer', 'Updated answer.');

            $this->assertDatabaseHas('faqs', [
                'id' => $faq->id,
                'question' => 'Updated question?',
                'answer' => 'Updated answer.'
            ]);
        });

        it('admin can delete any faq', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'FAQ deleted successfully');

            $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
        });

        it('admin can view faq from any course or bootcamp', function () {
            $courseFaq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->draftCourse->id
            ]);
            $bootcampFaq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->draftBootcamp->id
            ]);

            getJson("/api/faqs/{$courseFaq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $courseFaq->id);

            getJson("/api/faqs/{$bootcampFaq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $bootcampFaq->id);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see faqs only from their own courses and bootcamps', function () {
            Faq::factory()->count(3)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->otherCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);
            Faq::factory()->count(1)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->otherBootcamp->id
            ]);

            getJson('/api/faqs')
                ->assertOk();
        });

        it('instructor can create faqs for their own course', function () {
            $faqData = [
                'faqable_type' => 'App\\Models\\Course',
                'faqable_id' => $this->publishedCourse->id,
                'faqs' => [
                    [
                        'question' => 'How to start?',
                        'answer' => 'Start with basics.'
                    ]
                ]
            ];

            postJson('/api/faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can create faqs for their own bootcamp', function () {
            $faqData = [
                'faqable_type' => 'App\\Models\\Bootcamp',
                'faqable_id' => $this->publishedBootcamp->id,
                'faqs' => [
                    [
                        'question' => 'What to bring?',
                        'answer' => 'Bring your laptop.'
                    ]
                ]
            ];

            postJson('/api/faqs', $faqData)
                ->assertCreated()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can update faq from their own course', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            putJson("/api/faqs/{$faq->id}", [
                'question' => 'Updated by instructor?',
                'answer' => 'Yes, by instructor.'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can update faq from their own bootcamp', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);

            putJson("/api/faqs/{$faq->id}", [
                'question' => 'Updated bootcamp faq?',
                'answer' => 'Yes, updated.'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can delete faq from their own course', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can delete faq from their own bootcamp', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('instructor cannot update faq from other instructor course', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->otherCourse->id
            ]);

            putJson("/api/faqs/{$faq->id}", [
                'question' => 'Unauthorized?'
            ])
                ->assertForbidden();
        });

        it('instructor cannot update faq from other instructor bootcamp', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->otherBootcamp->id
            ]);

            putJson("/api/faqs/{$faq->id}", [
                'question' => 'Unauthorized?'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete faq from other instructor course', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->otherCourse->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('instructor cannot delete faq from other instructor bootcamp', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->otherBootcamp->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view faqs from published courses and bootcamps only', function () {
            Faq::factory()->count(3)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->draftCourse->id
            ]);
            Faq::factory()->count(2)->create([
                'faqable_type' => Bootcamp::class,
                'faqable_id' => $this->publishedBootcamp->id
            ]);

            getJson('/api/faqs')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('student can view single faq from published course', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            getJson("/api/faqs/{$faq->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $faq->id);
        });

        it('student cannot view faq from draft course', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->draftCourse->id
            ]);

            getJson("/api/faqs/{$faq->id}")
                ->assertForbidden();
        });

        it('student cannot create faq', function () {
            postJson('/api/faqs', [
                'faqable_type' => 'App\\Models\\Course',
                'faqable_id' => $this->publishedCourse->id,
                'faqs' => [
                    [
                        'question' => 'Student question?',
                        'answer' => 'Student answer.'
                    ]
                ]
            ])
                ->assertForbidden();
        });

        it('student cannot update faq', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            putJson("/api/faqs/{$faq->id}", [
                'question' => 'Unauthorized?'
            ])
                ->assertForbidden();
        });

        it('student cannot delete faq', function () {
            $faq = Faq::factory()->create([
                'faqable_type' => Course::class,
                'faqable_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/faqs/{$faq->id}")
                ->assertForbidden();
        });
    });
});
