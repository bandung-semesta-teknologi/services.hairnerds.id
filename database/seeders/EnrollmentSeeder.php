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
        $users = User::where('role', 'student')->take(10)->get();
        $courses = Course::published()->take(8)->get();

        if ($users->isEmpty()) {
            $users = User::factory()->count(10)->create(['role' => 'student']);
        }

        if ($courses->isEmpty()) {
            $courses = Course::factory()->published()->verified()->count(5)->create();
        }

        foreach ($users as $user) {
            $enrolledCourses = $courses->random(rand(2, 4));

            foreach ($enrolledCourses as $course) {
                Enrollment::factory()->create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ]);
            }
        }

        Enrollment::factory()->finished()->count(10)->create();
        Enrollment::factory()->active()->count(15)->create();
    }
}
