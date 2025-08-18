<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(5)->create();
        }

        Course::factory()
            ->count(10)
            ->verified()
            ->create()
            ->each(function ($course) use ($categories) {
                $randomCategories = $categories->random(rand(1, 3));
                $course->categories()->attach($randomCategories->pluck('id'));
            });
    }
}
