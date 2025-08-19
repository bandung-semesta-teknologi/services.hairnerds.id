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
        $users = User::all();

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(5)->create();
        }

        if ($users->isEmpty()) {
            $users = User::factory()->count(10)->create();
        }

        Course::factory()
            ->count(10)
            ->verified()
            ->create()
            ->each(function ($course) use ($categories, $users) {
                $randomCategories = $categories->random(rand(1, 3));
                $course->categories()->attach($randomCategories->pluck('id'));

                $randomInstructors = $users->random(rand(1, 2));
                $course->instructors()->attach($randomInstructors->pluck('id'));
            });
    }
}
