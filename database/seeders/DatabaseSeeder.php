<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createBaseUsers();
        $this->createCategories();
        $this->createCourses();
        $this->createBootcamps();
        $this->callRelatedSeeders();
    }

    private function createBaseUsers(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        UserProfile::create([
            'user_id' => $admin->id,
            'address' => '123 Admin Street, Jakarta',
            'date_of_birth' => '1990-01-01',
        ]);

        UserCredential::create([
            'user_id' => $admin->id,
            'type' => 'email',
            'identifier' => $admin->email,
            'verified_at' => now(),
        ]);

        $instructor = User::create([
            'name' => 'Instructor John',
            'email' => 'instructor@mail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'instructor',
        ]);

        UserProfile::create([
            'user_id' => $instructor->id,
            'address' => '456 Instructor Avenue, Bandung',
            'date_of_birth' => '1985-05-15',
        ]);

        UserCredential::create([
            'user_id' => $instructor->id,
            'type' => 'email',
            'identifier' => $instructor->email,
            'verified_at' => now(),
        ]);

        $instructor2 = User::create([
            'name' => 'Instructor Jane',
            'email' => 'instructor2@mail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'instructor',
        ]);

        UserProfile::create([
            'user_id' => $instructor2->id,
            'address' => '789 Instructor Road, Surabaya',
            'date_of_birth' => '1988-08-20',
        ]);

        UserCredential::create([
            'user_id' => $instructor2->id,
            'type' => 'email',
            'identifier' => $instructor2->email,
            'verified_at' => now(),
        ]);

        $instructor3 = User::create([
            'name' => 'Master Barber Mike',
            'email' => 'instructor3@mail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'instructor',
        ]);

        UserProfile::create([
            'user_id' => $instructor3->id,
            'address' => '321 Barber Street, Medan',
            'date_of_birth' => '1982-03-10',
        ]);

        UserCredential::create([
            'user_id' => $instructor3->id,
            'type' => 'email',
            'identifier' => $instructor3->email,
            'verified_at' => now(),
        ]);

        User::factory(8)->create()->each(function ($user) {
            UserProfile::factory()->for($user)->create();
            UserCredential::factory()->for($user)->emailCredential($user->email)->create();
            UserCredential::factory()->for($user)->phoneCredential()->create();
        });
    }

    private function createCategories(): void
    {
        $categories = [
            'Basic Haircut Techniques',
            'Advanced Styling',
            'Beard Grooming',
            'Hair Washing & Treatment',
            'Fade Techniques',
            'Business Skills',
            'Customer Service',
            'Scissor Techniques',
            'Clipper Mastery',
            'Hair Color & Highlights'
        ];

        foreach ($categories as $categoryName) {
            Category::create(['name' => $categoryName]);
        }
    }

    private function createCourses(): void
    {
        $categories = Category::all();
        $instructors = User::where('role', 'instructor')->get();

        Course::factory()->count(5)->published()->verified()->create()->each(function ($course) use ($categories, $instructors) {
            $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
            $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
        });

        Course::factory()->count(2)->published()->verified()->highlight()->create()->each(function ($course) use ($categories, $instructors) {
            $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
            $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
        });

        Course::factory()->count(2)->notpublished()->verified()->create()->each(function ($course) use ($categories, $instructors) {
            $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
            $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
        });

        Course::factory()->count(3)->draft()->create([
            'verified_at' => null
        ])->each(function ($course) use ($categories, $instructors) {
            $course->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
            $course->instructors()->attach($instructors->random(1)->pluck('id'));
        });

        Course::factory()->count(1)->rejected()->verified()->create()->each(function ($course) use ($categories, $instructors) {
            $course->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
            $course->instructors()->attach($instructors->random(1)->pluck('id'));
        });
    }

    private function createBootcamps(): void
    {
        $categories = Category::all();
        $instructors = User::where('role', 'instructor')->get();

        foreach ($instructors as $instructor) {
            \App\Models\Bootcamp::factory()->count(2)->published()->verified()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) use ($categories) {
                $bootcamp->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
            });

            \App\Models\Bootcamp::factory()->count(2)->draft()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) use ($categories) {
                $bootcamp->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
            });

            \App\Models\Bootcamp::factory()->count(1)->rejected()->verified()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) use ($categories) {
                $bootcamp->categories()->attach($categories->random(1)->pluck('id'));
            });
        }

        \App\Models\Bootcamp::factory()->count(2)->unpublished()->verified()->create()->each(function ($bootcamp) use ($categories) {
            $bootcamp->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
        });
    }

    private function callRelatedSeeders(): void
    {
        $this->call([
            CourseFaqSeeder::class,
            SectionSeeder::class,
            LessonSeeder::class,
            ReviewSeeder::class,
            QuizSeeder::class,
            QuestionSeeder::class,
            AnswerBankSeeder::class,
            EnrollmentSeeder::class,
            ProgressSeeder::class,
            QuizResultSeeder::class,
        ]);
    }
}
