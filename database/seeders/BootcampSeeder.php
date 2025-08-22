<?php

namespace Database\Seeders;

use App\Models\Bootcamp;
use App\Models\User;
use Illuminate\Database\Seeder;

class BootcampSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::where('role', 'instructor')->get();

        if ($instructors->isEmpty()) {
            $instructors = User::factory()->instructor()->count(3)->create();
        }

        foreach ($instructors as $instructor) {
            Bootcamp::factory()->count(3)->published()->verified()->create([
                'user_id' => $instructor->id
            ]);

            Bootcamp::factory()->count(2)->draft()->create([
                'user_id' => $instructor->id
            ]);

            Bootcamp::factory()->count(1)->rejected()->verified()->create([
                'user_id' => $instructor->id
            ]);
        }

        Bootcamp::factory()->count(3)->unpublished()->verified()->create();
    }
}
