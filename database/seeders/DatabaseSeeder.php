<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(5)->create()->each(function ($user) {
            UserProfile::factory()->create([
                'user_id' => $user->id,
            ]);

            UserCredential::factory()->emailCredential()->create([
                'user_id' => $user->id,
                'identifier' => $user->email,
            ]);

            UserCredential::factory()->phoneCredential()->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
