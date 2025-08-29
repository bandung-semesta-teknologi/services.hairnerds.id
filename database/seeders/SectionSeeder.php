<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::whereNotNull('verified_at')->get();

        if ($courses->isEmpty()) {
            $this->command->warn('No verified courses found. Skipping Section seeding.');
            return;
        }

        foreach ($courses as $course) {
            $sectionCount = 4;

            for ($i = 1; $i <= $sectionCount; $i++) {
                Section::factory()->create([
                    'course_id' => $course->id,
                    'sequence' => $i
                ]);
            }
        }
    }
}
