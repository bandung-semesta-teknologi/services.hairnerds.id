<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $sections = Section::with(['course', 'lessons'])->get();

        if ($sections->isEmpty()) {
            $this->command->warn('No sections found. Skipping Quiz seeding.');
            return;
        }

        foreach ($sections as $section) {
            $lessons = $section->lessons;

            if ($lessons->isEmpty()) {
                continue;
            }

            $quizCount = 2;

            for ($i = 0; $i < $quizCount; $i++) {
                $randomLesson = $lessons->random();

                Quiz::factory()->create([
                    'section_id' => $section->id,
                    'lesson_id' => $randomLesson->id,
                    'course_id' => $section->course_id,
                ]);
            }
        }
    }
}
