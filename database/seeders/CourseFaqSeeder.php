<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Database\Seeder;

class CourseFaqSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::whereNotNull('verified_at')->get();

        if ($courses->isEmpty()) {
            $this->command->warn('No verified courses found. Skipping CourseFaq seeding.');
            return;
        }

        foreach ($courses as $course) {
            CourseFaq::factory()
                ->count(rand(3, 5))
                ->create(['course_id' => $course->id]);
        }
    }
}
