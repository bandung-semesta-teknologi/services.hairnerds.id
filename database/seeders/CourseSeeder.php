<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $instructors = User::where('role', 'instructor')->get();

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(5)->create();
        }

        if ($instructors->isEmpty()) {
            $instructors = User::factory()->instructor()->count(3)->create();
        }

        $courses = collect();

        $courses = $courses->merge(
            Course::factory()->count(6)->published()->verified()->create()->each(function ($course) use ($categories, $instructors) {
                $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
                $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
            })
        );

        $courses = $courses->merge(
            Course::factory()->count(1)->notpublished()->verified()->create()->each(function ($course) use ($categories, $instructors) {
                $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
                $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
            })
        );

        $courses = $courses->merge(
            Course::factory()->count(2)->draft()->create([
                'verified_at' => null
            ])->each(function ($course) use ($categories, $instructors) {
                $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
                $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
            })
        );

        $courses = $courses->merge(
            Course::factory()->count(1)->takedown()->verified()->create()->each(function ($course) use ($categories, $instructors) {
                $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
                $course->instructors()->attach($instructors->random(rand(1, 2))->pluck('id'));
            })
        );
    }
}
