<?php

use App\Models\Bootcamp;
use App\Models\Category;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('bootcamp crud api', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        $this->categories = Category::factory()->count(3)->create();
    });

    describe('public access', function () {
        it('public can only see published bootcamps', function () {
            Bootcamp::factory()->count(3)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->random(2)->pluck('id'));
            });
            Bootcamp::factory()->count(2)->draft()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->random(1)->pluck('id'));
            });
            Bootcamp::factory()->count(1)->rejected()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->random(1)->pluck('id'));
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'start_at',
                            'end_at',
                            'seat',
                            'seat_available',
                            'seat_blocked',
                            'seat_taken',
                            'description',
                            'short_description',
                            'categories',
                            'status',
                            'price',
                            'location',
                            'contact_person',
                            'url_location',
                            'verified_at',
                            'is_available',
                            'duration_days',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('public can filter published bootcamps by category', function () {
            $category1 = $this->categories->first();
            $category2 = $this->categories->last();

            $bootcamps1 = Bootcamp::factory()->count(3)->published()->create();
            $bootcamps2 = Bootcamp::factory()->count(2)->published()->create();
            Bootcamp::factory()->count(2)->draft()->create();

            $bootcamps1->each(fn($bootcamp) => $bootcamp->categories()->attach($category1->id));
            $bootcamps2->each(fn($bootcamp) => $bootcamp->categories()->attach($category2->id));

            getJson("/api/bootcamps?category_id={$category1->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('public can filter published bootcamps by location', function () {
            Bootcamp::factory()->published()->create(['location' => 'Hairnerds Academy Jakarta'])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->published()->create(['location' => 'Hairnerds Studio Bandung'])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->draft()->create(['location' => 'Hairnerds Academy Jakarta'])->categories()->attach($this->categories->first()->id);

            getJson('/api/bootcamps?location=Jakarta')
                ->assertOk()
                ->assertJsonCount(1, 'data');
        });

        it('public can search published bootcamps by title', function () {
            Bootcamp::factory()->published()->create(['title' => 'Advanced Barbering Bootcamp'])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->published()->create(['title' => 'Basic Hair Cutting'])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->draft()->create(['title' => 'Advanced Styling Draft'])->categories()->attach($this->categories->first()->id);

            getJson('/api/bootcamps?search=Advanced')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.title', 'Advanced Barbering Bootcamp');
        });

        it('public can filter bootcamps by availability', function () {
            Bootcamp::factory()->published()->create(['seat_available' => 5])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->published()->create(['seat_available' => 0])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->draft()->create(['seat_available' => 3])->categories()->attach($this->categories->first()->id);

            getJson('/api/bootcamps?available=1')
                ->assertOk()
                ->assertJsonCount(1, 'data');

            getJson('/api/bootcamps?available=0')
                ->assertOk()
                ->assertJsonCount(1, 'data');
        });

        it('public can filter bootcamps by price range', function () {
            Bootcamp::factory()->published()->create(['price' => 1000000])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->published()->create(['price' => 5000000])->categories()->attach($this->categories->first()->id);
            Bootcamp::factory()->published()->create(['price' => 10000000])->categories()->attach($this->categories->first()->id);

            getJson('/api/bootcamps?price_min=2000000&price_max=8000000')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.price', 5000000);
        });

        it('public can get published bootcamp details', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->take(2)->pluck('id'));

            getJson("/api/bootcamps/{$bootcamp->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $bootcamp->id)
                ->assertJsonPath('data.status', 'publish')
                ->assertJsonCount(2, 'data.categories')
                ->assertJsonStructure([
                    'data' => [
                        'instructor',
                        'categories'
                    ]
                ]);
        });

        it('public cannot see draft bootcamp details', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();
            $draftBootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$draftBootcamp->id}")
                ->assertNotFound();
        });

        it('public cannot see rejected bootcamp details', function () {
            $rejectedBootcamp = Bootcamp::factory()->rejected()->create();
            $rejectedBootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$rejectedBootcamp->id}")
                ->assertNotFound();
        });

        it('public cannot see unpublished bootcamps', function () {
            Bootcamp::factory()->count(2)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(1)->unpublished()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('returns 404 when bootcamp not found', function () {
            getJson('/api/bootcamps/99999')
                ->assertNotFound();
        });

        it('public can set custom per_page for pagination', function () {
            Bootcamp::factory()->count(10)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->random()->id);
            });

            getJson('/api/bootcamps?per_page=5')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('orders bootcamps by latest first', function () {
            $older = Bootcamp::factory()->published()->create(['created_at' => now()->subDay()]);
            $newer = Bootcamp::factory()->published()->create(['created_at' => now()]);

            $older->categories()->attach($this->categories->first()->id);
            $newer->categories()->attach($this->categories->first()->id);

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonPath('data.0.id', $newer->id)
                ->assertJsonPath('data.1.id', $older->id);
        });

        it('loads all required relationships on index', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->first()->id);

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'instructor',
                            'categories'
                        ]
                    ]
                ]);
        });

        it('loads all required relationships on show', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$bootcamp->id}")
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'instructor',
                        'categories'
                    ]
                ]);
        });

        it('includes computed fields in response', function () {
            $bootcamp = Bootcamp::factory()->published()->create([
                'seat' => 20,
                'seat_available' => 15,
                'seat_blocked' => 2
            ]);
            $bootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$bootcamp->id}")
                ->assertOk()
                ->assertJsonPath('data.seat_taken', 3)
                ->assertJsonPath('data.is_available', true)
                ->assertJsonStructure([
                    'data' => [
                        'seat_taken',
                        'is_available',
                        'duration_days'
                    ]
                ]);
        });

        it('includes status field in response', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$bootcamp->id}")
                ->assertOk()
                ->assertJsonPath('data.status', 'publish')
                ->assertJsonStructure([
                    'data' => [
                        'status'
                    ]
                ]);
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs(User::factory()->admin()->create());
        });

        it('admin can see all bootcamps regardless of status', function () {
            Bootcamp::factory()->count(2)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(2)->draft()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(1)->rejected()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('admin can filter bootcamps by status', function () {
            Bootcamp::factory()->count(2)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(3)->draft()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/bootcamps?status=draft')
                ->assertOk()
                ->assertJsonCount(3, 'data');

            getJson('/api/bootcamps?status=publish')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('admin can filter bootcamps by category', function () {
            $category1 = $this->categories->first();
            $category2 = $this->categories->last();

            Bootcamp::factory()->count(2)->published()->create()->each(fn($bootcamp) => $bootcamp->categories()->attach($category1->id));
            Bootcamp::factory()->count(1)->draft()->create()->each(fn($bootcamp) => $bootcamp->categories()->attach($category2->id));

            getJson("/api/bootcamps?category_id={$category1->id}")
                ->assertOk()
                ->assertJsonCount(2, 'data');

            getJson("/api/bootcamps?category_id={$category2->id}")
                ->assertOk()
                ->assertJsonCount(1, 'data');
        });

        it('admin can see draft bootcamp details', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();
            $draftBootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$draftBootcamp->id}")
                ->assertOk()
                ->assertJsonPath('data.status', 'draft');
        });

        it('admin can see rejected bootcamps', function () {
            Bootcamp::factory()->count(1)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(1)->rejected()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('admin can create bootcamp', function () {
            $bootcampData = [
                'title' => 'Professional Barbering Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 25,
                'description' => 'Complete barbering course',
                'short_description' => 'Learn professional barbering',
                'category_ids' => [$this->categories->first()->id, $this->categories->last()->id],
                'price' => 5000000,
                'location' => 'Hairnerds Academy Jakarta',
                'contact_person' => 'Master Barber John',
                'url_location' => 'https://maps.google.com/hairnerds-jakarta',
                'status' => 'publish',
                'verified_at' => now()->toDateString()
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Professional Barbering Bootcamp')
                ->assertJsonPath('data.status', 'publish')
                ->assertJsonPath('data.seat', 25)
                ->assertJsonPath('data.seat_available', 25)
                ->assertJsonCount(2, 'data.categories')
                ->assertJsonCount(2, 'data.categories');

            $this->assertDatabaseHas('bootcamps', [
                'title' => 'Professional Barbering Bootcamp',
                'status' => 'publish'
            ]);
        });

        it('admin can update bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();
            $bootcamp->categories()->attach($this->categories->first()->id);

            $updateData = [
                'title' => 'Updated Bootcamp Title',
                'price' => 7500000,
                'seat' => 30,
                'category_ids' => $this->categories->take(2)->pluck('id')->toArray(),
                'status' => 'publish'
            ];

            putJson("/api/bootcamps/{$bootcamp->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('data.title', 'Updated Bootcamp Title')
                ->assertJsonPath('data.price', 7500000)
                ->assertJsonPath('data.seat', 30)
                ->assertJsonPath('data.status', 'publish')
                ->assertJsonCount(2, 'data.categories');

            $this->assertDatabaseHas('bootcamps', [
                'id' => $bootcamp->id,
                'title' => 'Updated Bootcamp Title',
                'price' => 7500000,
                'status' => 'publish'
            ]);
        });

        it('admin can update bootcamp categories', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->first()->id);

            $updateData = [
                'category_ids' => $this->categories->take(2)->pluck('id')->toArray()
            ];

            putJson("/api/bootcamps/{$bootcamp->id}", $updateData)
                ->assertOk()
                ->assertJsonCount(2, 'data.categories');

            expect($bootcamp->fresh()->categories()->count())->toBe(2);
        });

        it('admin can delete bootcamp', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->first()->id);

            deleteJson("/api/bootcamps/{$bootcamp->id}")
                ->assertOk()
                ->assertJsonPath('message', 'Bootcamp deleted successfully');

            $this->assertSoftDeleted('bootcamps', ['id' => $bootcamp->id]);
        });

        it('admin can verify draft bootcamp to publish', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create(['verified_at' => null]);

            postJson("/api/bootcamps/{$draftBootcamp->id}/verify", [
                'status' => 'publish',
                'notes' => 'Bootcamp looks excellent'
            ])
                ->assertOk()
                ->assertJsonPath('data.status', 'publish')
                ->assertJsonPath('message', 'Bootcamp verified successfully');

            $this->assertDatabaseHas('bootcamps', [
                'id' => $draftBootcamp->id,
                'status' => 'publish'
            ]);

            expect($draftBootcamp->fresh()->verified_at)->not()->toBeNull();
        });

        it('admin can verify draft bootcamp to unpublish', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create(['verified_at' => null]);

            postJson("/api/bootcamps/{$draftBootcamp->id}/verify", [
                'status' => 'unpublish'
            ])
                ->assertOk()
                ->assertJsonPath('data.status', 'unpublish');
        });

        it('admin can reject draft bootcamp', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create(['verified_at' => null]);

            postJson("/api/bootcamps/{$draftBootcamp->id}/reject")
                ->assertOk()
                ->assertJsonPath('data.status', 'rejected')
                ->assertJsonPath('message', 'Bootcamp rejected successfully');

            $this->assertDatabaseHas('bootcamps', [
                'id' => $draftBootcamp->id,
                'status' => 'rejected'
            ]);

            expect($draftBootcamp->fresh()->verified_at)->not()->toBeNull();
        });

        it('cannot verify non-draft bootcamp', function () {
            $publishedBootcamp = Bootcamp::factory()->published()->create();

            postJson("/api/bootcamps/{$publishedBootcamp->id}/verify", [
                'status' => 'publish'
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Only draft bootcamps can be verified');
        });

        it('cannot reject non-draft bootcamp', function () {
            $publishedBootcamp = Bootcamp::factory()->published()->create();

            postJson("/api/bootcamps/{$publishedBootcamp->id}/reject")
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Only draft bootcamps can be rejected');
        });

        it('validates verification request', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$draftBootcamp->id}/verify", [
                'status' => 'invalid_status'
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status']);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs(User::factory()->instructor()->create());
        });

        it('instructor can see all bootcamps regardless of status', function () {
            Bootcamp::factory()->count(2)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(2)->draft()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });

        it('instructor can see draft bootcamp details', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();
            $draftBootcamp->categories()->attach($this->categories->first()->id);

            getJson("/api/bootcamps/{$draftBootcamp->id}")
                ->assertOk()
                ->assertJsonPath('data.status', 'draft');
        });

        it('instructor can create bootcamp', function () {
            $bootcampData = [
                'title' => 'Instructor Barbering Workshop',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(3)->format('Y-m-d H:i:s'),
                'seat' => 15,
                'description' => 'Basic barbering techniques',
                'category_ids' => [$this->categories->first()->id],
                'price' => 2500000,
                'location' => 'Hairnerds Studio Bandung',
                'contact_person' => 'Instructor Jane'
            ];

            $response = postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Instructor Barbering Workshop')
                ->assertJsonPath('data.status', 'draft')
                ->assertJsonCount(1, 'data.categories');

            $this->assertDatabaseHas('bootcamps', [
                'title' => 'Instructor Barbering Workshop',
                'status' => 'draft'
            ]);
        });

        it('bootcamp defaults to draft status when not specified', function () {
            $bootcampData = [
                'title' => 'Default Status Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(2)->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.status', 'draft');
        });

        it('seat_available defaults to seat value when not specified', function () {
            $bootcampData = [
                'title' => 'Auto Seat Available Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(2)->format('Y-m-d H:i:s'),
                'seat' => 25,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.seat_available', 25);
        });

        it('instructor can create bootcamp with multiple categories', function () {
            $bootcampData = [
                'title' => 'Multi Category Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(2)->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => $this->categories->take(2)->pluck('id')->toArray(),
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Multi Category Bootcamp')
                ->assertJsonCount(2, 'data.categories');
        });

        it('instructor can update bootcamp', function () {
            $instructor = auth()->user();
            $bootcamp = Bootcamp::factory()->draft()->create(['user_id' => $instructor->id]);
            $bootcamp->categories()->attach($this->categories->first()->id);

            $updateData = [
                'title' => 'Instructor Updated Bootcamp',
                'price' => 3500000,
                'seat' => 30,
                'category_ids' => $this->categories->take(2)->pluck('id')->toArray()
            ];

            putJson("/api/bootcamps/{$bootcamp->id}", $updateData)
                ->assertOk()
                ->assertJsonPath('data.title', 'Instructor Updated Bootcamp')
                ->assertJsonPath('data.price', 3500000)
                ->assertJsonPath('data.seat', 30)
                ->assertJsonCount(2, 'data.categories');
        });

        it('instructor can delete bootcamp', function () {
            $instructor = auth()->user();
            $bootcamp = Bootcamp::factory()->draft()->create(['user_id' => $instructor->id]);
            $bootcamp->categories()->attach($this->categories->first()->id);

            deleteJson("/api/bootcamps/{$bootcamp->id}")
                ->assertOk()
                ->assertJsonPath('message', 'Bootcamp deleted successfully');

            $this->assertSoftDeleted('bootcamps', ['id' => $bootcamp->id]);
        });

        it('instructor editing rejected bootcamp resets to draft', function () {
            $instructor = auth()->user();
            $rejectedBootcamp = Bootcamp::factory()->rejected()->verified()->create(['user_id' => $instructor->id]);
            $rejectedBootcamp->categories()->attach($this->categories->first()->id);

            putJson("/api/bootcamps/{$rejectedBootcamp->id}", [
                'title' => 'Updated Rejected Bootcamp'
            ])
                ->assertOk()
                ->assertJsonPath('data.status', 'draft')
                ->assertJsonPath('data.title', 'Updated Rejected Bootcamp');

            $fresh = $rejectedBootcamp->fresh();
            expect($fresh->status)->toBe('draft');
            expect($fresh->verified_at)->toBeNull();
        });

        it('instructor cannot verify bootcamp', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$draftBootcamp->id}/verify", [
                'status' => 'publish'
            ])
                ->assertForbidden();
        });

        it('instructor cannot reject bootcamp', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$draftBootcamp->id}/reject")
                ->assertForbidden();
        });

        it('validates required fields when creating bootcamp', function () {
            postJson('/api/bootcamps', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['title', 'start_at', 'end_at', 'seat', 'category_ids', 'location', 'contact_person']);
        });

        it('validates date fields when creating bootcamp', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['start_at', 'end_at']);
        });

        it('validates seat availability constraints', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 10,
                'seat_available' => 15,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['seat_available']);
        });

        it('validates status enum when creating bootcamp', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person',
                'status' => 'invalid_status'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status']);
        });

        it('validates category_ids exist', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [99999], // Non-existent category
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['category_ids.0']);
        });

        it('validates url_location format', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person',
                'url_location' => 'invalid-url'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['url_location']);
        });

        it('validates status enum when updating bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();

            putJson("/api/bootcamps/{$bootcamp->id}", ['status' => 'invalid_status'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status']);
        });

        it('validates category_ids when updating bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();

            putJson("/api/bootcamps/{$bootcamp->id}", [
                'category_ids' => [99999] // Non-existent category
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['category_ids.0']);
        });

        it('returns 404 when deleting non-existent bootcamp', function () {
            deleteJson('/api/bootcamps/99999')
                ->assertNotFound();
        });
    });

    describe('student access (forbidden)', function () {
        beforeEach(function () {
            actingAs($this->user);
        });

        it('student cannot create bootcamp', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertForbidden();
        });

        it('student cannot update bootcamp', function () {
            $bootcamp = Bootcamp::factory()->published()->create();

            putJson("/api/bootcamps/{$bootcamp->id}", ['title' => 'Updated Title'])
                ->assertForbidden();
        });

        it('student cannot delete bootcamp', function () {
            $bootcamp = Bootcamp::factory()->published()->create();

            deleteJson("/api/bootcamps/{$bootcamp->id}")
                ->assertForbidden();
        });

        it('student cannot verify bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$bootcamp->id}/verify", ['status' => 'publish'])
                ->assertForbidden();
        });

        it('student cannot reject bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$bootcamp->id}/reject")
                ->assertForbidden();
        });
    });

    describe('guest access (unauthenticated)', function () {
        it('guest cannot create bootcamp', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addWeek()->format('Y-m-d H:i:s'),
                'seat' => 20,
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Test Location',
                'contact_person' => 'Test Person'
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertUnauthorized();
        });

        it('guest cannot update bootcamp', function () {
            $bootcamp = Bootcamp::factory()->published()->create();

            putJson("/api/bootcamps/{$bootcamp->id}", ['title' => 'Updated Title'])
                ->assertUnauthorized();
        });

        it('guest cannot delete bootcamp', function () {
            $bootcamp = Bootcamp::factory()->published()->create();

            deleteJson("/api/bootcamps/{$bootcamp->id}")
                ->assertUnauthorized();
        });

        it('guest cannot verify bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$bootcamp->id}/verify", ['status' => 'publish'])
                ->assertUnauthorized();
        });

        it('guest cannot reject bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create();

            postJson("/api/bootcamps/{$bootcamp->id}/reject")
                ->assertUnauthorized();
        });
    });
});
