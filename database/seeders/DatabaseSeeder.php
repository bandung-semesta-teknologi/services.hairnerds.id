<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
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

        User::factory(5)->create()->each(function ($user) {
            UserProfile::factory()->for($user)->create();
            UserCredential::factory()->for($user)->emailCredential($user->email)->create();
            UserCredential::factory()->for($user)->phoneCredential()->create();
        });

        $this->call([
            CategorySeeder::class,
            CourseSeeder::class,
            CourseFaqSeeder::class,
            SectionSeeder::class,
            LessonSeeder::class,
        ]);
    }
}
