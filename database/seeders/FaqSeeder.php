<?php

namespace Database\Seeders;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::whereNotNull('verified_at')->get();

        if ($courses->isNotEmpty()) {
            foreach ($courses as $course) {
                Faq::factory()
                    ->count(rand(2, 4))
                    ->create([
                        'faqable_type' => Course::class,
                        'faqable_id' => $course->id,
                    ]);
            }
        }

        $bootcamps = Bootcamp::whereNotNull('verified_at')->get();

        if ($bootcamps->isNotEmpty()) {
            foreach ($bootcamps as $bootcamp) {
                Faq::factory()
                    ->count(rand(2, 3))
                    ->create([
                        'faqable_type' => Bootcamp::class,
                        'faqable_id' => $bootcamp->id,
                    ]);
            }
        }
    }
}
