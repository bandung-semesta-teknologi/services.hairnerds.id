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
        Bootcamp::query()->forceDelete();

        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        $this->categories = Category::factory()->count(3)->create();
    });

    describe('guest access', function () {
        it('guest can only see published bootcamps', function () {
            Bootcamp::factory()->count(3)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->random(2)->pluck('id'));
            });
            Bootcamp::factory()->count(2)->draft()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->random(2)->pluck('id'));
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('guest can get published bootcamp details', function () {
            $bootcamp = Bootcamp::factory()->published()->create();
            $bootcamp->categories()->attach($this->categories->take(2)->pluck('id'));

            getJson("/api/bootcamps/{$bootcamp->slug}")
                ->assertOk()
                ->assertJsonPath('data.status', 'publish');
        });

        it('guest cannot see draft bootcamp details', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create();

            getJson("/api/bootcamps/{$draftBootcamp->slug}")
                ->assertForbidden();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            $this->admin = User::factory()->admin()->create();
            actingAs($this->admin);
        });

        it('admin can see all bootcamps regardless of status', function () {
            $instructor = User::factory()->instructor()->create();

            Bootcamp::factory()->count(2)->published()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            Bootcamp::factory()->count(2)->draft()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            Bootcamp::factory()->count(1)->rejected()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            expect(Bootcamp::count())->toBe(5);

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('admin can filter bootcamps by status', function () {
            $instructor = User::factory()->instructor()->create();

            Bootcamp::factory()->count(2)->published()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            Bootcamp::factory()->count(3)->draft()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            expect(Bootcamp::count())->toBe(5);
            expect(Bootcamp::where('status', 'draft')->count())->toBe(3);
            expect(Bootcamp::where('status', 'publish')->count())->toBe(2);

            getJson('/api/bootcamps?status=draft')
                ->assertOk()
                ->assertJsonCount(3, 'data');

            getJson('/api/bootcamps?status=publish')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('admin can create bootcamp', function () {
            $bootcampData = [
                'title' => 'Test Bootcamp',
                'description' => 'This is a test bootcamp',
                'location' => 'Jakarta',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(7)->format('Y-m-d H:i:s'),
                'price' => 5000000,
                'seat' => 20,
                'contact_person' => 'Admin Test',
                'category_ids' => [$this->categories->first()->id],
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Test Bootcamp');
        });

        it('admin can verify draft bootcamp', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create(['verified_at' => null]);

            postJson("/api/bootcamps/{$draftBootcamp->slug}/verify", [
                'status' => 'publish'
            ])
                ->assertOk()
                ->assertJsonPath('data.status', 'publish');
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            $this->instructor = User::factory()->instructor()->create();
            actingAs($this->instructor);
        });

        it('instructor can see only their own bootcamps', function () {
            $otherInstructor = User::factory()->instructor()->create();

            Bootcamp::factory()->count(2)->published()->create([
                'user_id' => $this->instructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            Bootcamp::factory()->count(3)->published()->create([
                'user_id' => $otherInstructor->id
            ])->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            expect(Bootcamp::count())->toBe(5);
            expect(Bootcamp::where('user_id', $this->instructor->id)->count())->toBe(2);

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('instructor can create bootcamp', function () {
            $bootcampData = [
                'title' => 'Instructor Bootcamp',
                'description' => 'Bootcamp by instructor',
                'location' => 'Bandung',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(5)->format('Y-m-d H:i:s'),
                'price' => 3000000,
                'seat' => 15,
                'contact_person' => 'Instructor Test',
                'category_ids' => [$this->categories->first()->id],
            ];

            postJson('/api/bootcamps', $bootcampData)
                ->assertCreated()
                ->assertJsonPath('data.title', 'Instructor Bootcamp')
                ->assertJsonPath('data.status', 'draft');
        });

        it('instructor can update own bootcamp', function () {
            $bootcamp = Bootcamp::factory()->draft()->create([
                'user_id' => $this->instructor->id
            ]);
            $bootcamp->categories()->attach($this->categories->first()->id);

            putJson("/api/bootcamps/{$bootcamp->slug}", [
                'title' => 'Updated Bootcamp'
            ])
                ->assertOk()
                ->assertJsonPath('data.title', 'Updated Bootcamp');
        });

        it('instructor cannot update other instructor bootcamp', function () {
            $otherInstructor = User::factory()->instructor()->create();
            $bootcamp = Bootcamp::factory()->draft()->create([
                'user_id' => $otherInstructor->id
            ]);
            $bootcamp->categories()->attach($this->categories->first()->id);

            putJson("/api/bootcamps/{$bootcamp->slug}", ['title' => 'Updated Title'])
                ->assertForbidden();
        });

        it('instructor cannot verify bootcamp', function () {
            $draftBootcamp = Bootcamp::factory()->draft()->create([
                'user_id' => $this->instructor->id
            ]);

            postJson("/api/bootcamps/{$draftBootcamp->slug}/verify", [
                'status' => 'publish'
            ])
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->user);
        });

        it('student can only see published bootcamps', function () {
            Bootcamp::factory()->count(2)->published()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });
            Bootcamp::factory()->count(1)->draft()->create()->each(function ($bootcamp) {
                $bootcamp->categories()->attach($this->categories->first()->id);
            });

            getJson('/api/bootcamps')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('student cannot create bootcamp', function () {
            postJson('/api/bootcamps', [
                'title' => 'Test Bootcamp',
                'category_ids' => [$this->categories->first()->id],
                'location' => 'Jakarta',
                'start_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_at' => now()->addMonth()->addDays(5)->format('Y-m-d H:i:s'),
                'price' => 1000000,
                'seat' => 10,
                'contact_person' => 'Student Test'
            ])
                ->assertForbidden();
        });

        it('student cannot update bootcamp', function () {
            $bootcamp = Bootcamp::factory()->published()->create();

            putJson("/api/bootcamps/{$bootcamp->slug}", ['title' => 'Updated'])
                ->assertForbidden();
        });
    });
});
