<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'student')->get();
        $courses = Course::published()->get();

        if ($users->isEmpty()) {
            $this->command->warn('No student users found. Skipping Enrollment seeding.');
            return;
        }

        if ($courses->isEmpty()) {
            $this->command->warn('No published courses found. Skipping Enrollment seeding.');
            return;
        }

        foreach ($users as $user) {
            $enrolledCourses = $courses->random(min(rand(1, 2), $courses->count()));

            foreach ($enrolledCourses as $course) {
                if (!Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
                    $enrollment = Enrollment::factory()->create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ]);

                    if (fake()->boolean(20)) {
                        $enrollment->update(['finished_at' => fake()->dateTimeBetween('-1 month', 'now')]);
                    }
                }
            }
        }
    }
}
