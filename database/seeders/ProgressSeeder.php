<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Progress;
use Illuminate\Database\Seeder;

class ProgressSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = Enrollment::with(['course.lessons'])->get();

        if ($enrollments->isEmpty()) {
            $this->command->warn('No enrollments found. Skipping Progress seeding.');
            return;
        }

        foreach ($enrollments as $enrollment) {
            $lessons = $enrollment->course->lessons;

            if ($lessons->isEmpty()) {
                continue;
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
    }
}
