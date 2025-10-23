<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceCategory;
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
        $this->callMembershipSerialSeeder();
    }

    private function createBaseUsers(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@mail.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id' => $superAdmin->id,
            'address' => null,
            'avatar' => null,
            'date_of_birth' => null,
        ]);

        UserCredential::create([
            'user_id' => $superAdmin->id,
            'type' => 'email',
            'identifier' => $superAdmin->email,
            'verified_at' => now(),
        ]);

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

        $admin->socials()->createMany([
            ['type' => 'instagram', 'url' => 'https://instagram.com/admin_hairnerds'],
            ['type' => 'facebook', 'url' => 'https://facebook.com/admin.hairnerds'],
            ['type' => 'twitter', 'url' => 'https://twitter.com/admin_hairnerds'],
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

        $instructor->socials()->createMany([
            ['type' => 'instagram', 'url' => 'https://instagram.com/instructor_john'],
            ['type' => 'linkedin', 'url' => 'https://linkedin.com/in/instructor-john'],
            ['type' => 'youtube', 'url' => 'https://youtube.com/@instructorjohn'],
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

        $instructor2->socials()->createMany([
            ['type' => 'facebook', 'url' => 'https://facebook.com/instructor.jane'],
            ['type' => 'tiktok', 'url' => 'https://tiktok.com/@instructorjane'],
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

        $instructor3->socials()->createMany([
            ['type' => 'instagram', 'url' => 'https://instagram.com/masterbarber_mike'],
            ['type' => 'facebook', 'url' => 'https://facebook.com/masterbarber.mike'],
            ['type' => 'youtube', 'url' => 'https://youtube.com/@masterbarbermike'],
            ['type' => 'twitter', 'url' => 'https://twitter.com/barber_mike'],
        ]);

        User::factory(4)->create()->each(function ($user) {
            UserProfile::factory()->for($user)->create();
            UserCredential::factory()->for($user)->emailCredential($user->email)->create();
            UserCredential::factory()->for($user)->phoneCredential()->create();

            \App\Models\Social::factory(rand(2, 4))->for($user)->create();
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

        Course::factory()->count(3)->published()->verified()->create()->each(function ($course) use ($categories, $instructors) {
            $course->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
            $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
        });
    }

    private function createBootcamps(): void
    {
        $categories = Category::all();
        $instructors = User::where('role', 'instructor')->get();

        if ($instructors->isNotEmpty()) {
            \App\Models\Bootcamp::factory()->count(1)->published()->verified()->create()->each(function ($bootcamp) use ($categories, $instructors) {
                $bootcamp->categories()->attach($categories->random(1)->pluck('id'));
                $bootcamp->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
            });
        }
    }

    private function callRelatedSeeders(): void
    {
        $this->call([
            FaqSeeder::class,
            SectionSeeder::class,
            LessonSeeder::class,
            ReviewSeeder::class,
            QuizSeeder::class,
            QuestionSeeder::class,
            AnswerBankSeeder::class,
            EnrollmentSeeder::class,
            ProgressSeeder::class,
            StoreSeeder::class,
            BarberSeeder::class,
            CatalogCategorySeeder::class,
            ServiceSeeder::class,
            ServiceCategorySeeder::class,
            ServiceBarberSeeder::class,
        ]);
    }

    private function callMembershipSerialSeeder(): void
    {
        $this->call([
            MembershipSerialSeeder::class,
        ]);
    }
}
