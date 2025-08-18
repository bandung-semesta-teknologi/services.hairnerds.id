<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::whereNotNull('verified_at')->take(5)->get();

        if ($courses->isEmpty()) {
            $courses = Course::factory()->count(3)->verified()->create();
            $courses->each(function ($course) {
                $categories = \App\Models\Category::inRandomOrder()->take(2)->get();
                $course->categories()->attach($categories->pluck('id'));
            });
        }

        foreach ($courses as $course) {
            $sectionCount = rand(3, 7);

            for ($i = 1; $i <= $sectionCount; $i++) {
                Section::factory()->create([
                    'course_id' => $course->id,
                    'sequence' => $i
                ]);
            }
        }
    }
}
