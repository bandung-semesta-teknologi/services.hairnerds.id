<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $sections = Section::with('course')->take(10)->get();

        if ($sections->isEmpty()) {
            $sections = Section::factory()->count(5)->create();
        }

        foreach ($sections as $section) {
            $lessonCount = rand(3, 8);

            for ($i = 1; $i <= $lessonCount; $i++) {
                Lesson::factory()->create([
                    'section_id' => $section->id,
                    'course_id' => $section->course_id,
                    'sequence' => $i
                ]);
            }
        }
    }
}
