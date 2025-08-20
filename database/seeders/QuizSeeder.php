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
        $sections = Section::with(['course', 'lessons'])->take(10)->get();

        if ($sections->isEmpty()) {
            $sections = Section::factory()->count(5)->create();
        }

        foreach ($sections as $section) {
            $lessons = $section->lessons;

            if ($lessons->isEmpty()) {
                $lessons = Lesson::factory()->count(3)->create([
                    'section_id' => $section->id,
                    'course_id' => $section->course_id,
                ]);
            }

            $quizCount = rand(1, 3);

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
