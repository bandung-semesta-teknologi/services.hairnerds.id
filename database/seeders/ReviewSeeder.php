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
        $courses = Course::whereNotNull('verified_at')->take(5)->get();
        $users = User::take(10)->get();

        if ($courses->isEmpty()) {
            $courses = Course::factory()->count(3)->verified()->create();
            $courses->each(function ($course) {
                $categories = \App\Models\Category::inRandomOrder()->take(2)->get();
                $course->categories()->attach($categories->pluck('id'));
            });
        }

        if ($users->isEmpty()) {
            $users = User::factory()->count(10)->create();
        }

        foreach ($courses as $course) {
            $reviewCount = rand(5, 15);

            for ($i = 0; $i < $reviewCount; $i++) {
                Review::factory()->create([
                    'course_id' => $course->id,
                    'user_id' => $users->random()->id,
                ]);
            }
        }
    }
}
