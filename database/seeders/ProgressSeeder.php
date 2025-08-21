<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use Illuminate\Database\Seeder;

class ProgressSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = Enrollment::with(['course.lessons'])->take(20)->get();

        if ($enrollments->isEmpty()) {
            $enrollments = Enrollment::factory()->count(10)->create();
            $enrollments->load(['course.lessons']);
        }

        foreach ($enrollments as $enrollment) {
            $lessons = $enrollment->course->lessons;

            if ($lessons->isEmpty()) {
                $lessons = Lesson::factory()->count(5)->create([
                    'course_id' => $enrollment->course_id,
                ]);
            }

            foreach ($lessons as $lesson) {
                $isCompleted = fake()->boolean(60);

                Progress::factory()->create([
                    'enrollment_id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'course_id' => $enrollment->course_id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => $isCompleted,
                    'score' => $isCompleted && $lesson->type === 'quiz' ?
                        fake()->numberBetween(60, 100) : null,
                ]);
            }
        }

        Progress::factory()->completed()->count(20)->create();
        Progress::factory()->incomplete()->count(15)->create();
    }
}
