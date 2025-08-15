<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Database\Seeder;

class CourseFaqSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::published()->take(1)->get();

        if ($courses->isEmpty()) {
            Course::factory()->count(3)->published()->create();
            $courses = Course::published()->take(1)->get();
        }

        foreach ($courses as $course) {
            CourseFaq::factory()
                ->count(rand(3, 5))
                ->create(['course_id' => $course->id]);
        }
    }
}
