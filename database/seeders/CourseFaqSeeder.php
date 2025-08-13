<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Database\Seeder;

class CourseFaqSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::where('title', 'Fundamental Men\'s Haircut for Beginners')->first();

        if (!$course) {
            return;
        }

        $faqs = [
            [
                'course_id' => $course->id,
                'question' => 'Who is this course for?',
                'answer' => 'This course is designed for complete beginners who want to learn the basics of men\'s haircuts. Whether you\'re starting your barbering career or just want to cut hair at home, this course will give you the foundational skills you need.'
            ],
            [
                'course_id' => $course->id,
                'question' => 'Do I need any prior experience?',
                'answer' => 'No prior experience is required! This is a beginner-friendly course that starts from the very basics. We\'ll teach you everything from how to hold the tools properly to creating your first complete haircut.'
            ],
            [
                'course_id' => $course->id,
                'question' => 'What tools do I need for this course?',
                'answer' => 'You\'ll need basic barbering tools including hair clippers, scissors, a comb, and a cape or towel. We provide a detailed list of recommended tools in the course materials, including budget-friendly options for beginners.'
            ],
            [
                'course_id' => $course->id,
                'question' => 'How long does it take to complete the course?',
                'answer' => 'The course contains approximately 4 hours of video content, but we recommend taking your time to practice. Most students complete the course within 2-3 weeks, practicing the techniques as they learn.'
            ],
            [
                'course_id' => $course->id,
                'question' => 'Will I get a certificate after completion?',
                'answer' => 'Yes! Upon successful completion of all modules and practical assignments, you\'ll receive a digital certificate of completion that you can add to your professional portfolio or resume.'
            ]
        ];

        foreach ($faqs as $faq) {
            CourseFaq::create($faq);
        }
    }
}
