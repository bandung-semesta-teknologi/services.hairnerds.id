<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::published()->get();
        $users = User::where('role', 'student')->get();

        if ($courses->isEmpty()) {
            $this->command->warn('No published courses found. Skipping Review seeding.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No student users found. Skipping Review seeding.');
            return;
        }

        foreach ($courses as $course) {
            $reviewCount = rand(3, 8);

            for ($i = 0; $i < $reviewCount; $i++) {
                Review::factory()->create([
                    'course_id' => $course->id,
                    'user_id' => $users->random()->id,
                ]);
            }
        }
    }
}
