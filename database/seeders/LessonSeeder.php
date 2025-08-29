<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $sections = Section::with('course')->get();

        if ($sections->isEmpty()) {
            $this->command->warn('No sections found. Skipping Lesson seeding.');
            return;
        }

        foreach ($sections as $section) {
            $lessonCount = 2;

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
