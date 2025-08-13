<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $basicCategory = CourseCategory::where('name', 'Basic Haircut Techniques')->first();
        $fadeCategory = CourseCategory::where('name', 'Fade Techniques')->first();
        $beardCategory = CourseCategory::where('name', 'Beard Grooming')->first();

        $courses = [
            [
                'title' => 'Fundamental Men\'s Haircut for Beginners',
                'short_description' => 'Learn the essential techniques for basic men\'s haircuts, perfect for beginners starting their barbering journey.',
                'description' => 'This comprehensive course covers the fundamentals of men\'s haircuts including proper tool handling, basic cutting techniques, client consultation, and maintaining professional standards. You\'ll learn step-by-step methods to create classic cuts that every barber should master.',
                'what_will_learn' => '<ul><li>Basic knowledge of mens hair cut</li><li>Proper tool handling and maintenance</li><li>Client consultation techniques</li><li>Safety and hygiene standards</li></ul>',
                'requirements' => '<ul><li>No requirements</li></ul>',
                'category_id' => $basicCategory->id,
                'level' => 'beginner',
                'language' => 'english',
                'enable_drip_content' => false,
                'price' => 49.99,
                'status' => 'published'
            ],
            [
                'title' => 'Master the Perfect Fade Technique',
                'short_description' => 'Advanced course on creating seamless fade cuts with professional precision and consistency.',
                'description' => 'Take your barbering skills to the next level with advanced fade techniques. This course covers low, mid, and high fades, blending methods, and troubleshooting common fade problems. Perfect for barbers looking to perfect their signature fade.',
                'what_will_learn' => '<ul><li>Low, mid, and high fade techniques</li><li>Professional blending methods</li><li>Troubleshooting fade problems</li><li>Advanced clipper control</li></ul>',
                'requirements' => '<ul><li>Basic haircut knowledge</li><li>Professional clipper set</li></ul>',
                'category_id' => $fadeCategory->id,
                'level' => 'intermediate',
                'language' => 'english',
                'enable_drip_content' => true,
                'price' => 79.99,
                'status' => 'published'
            ],
            [
                'title' => 'Professional Beard Styling & Grooming',
                'short_description' => 'Complete guide to beard trimming, shaping, and styling for modern gentlemen.',
                'description' => 'Learn the art of beard grooming from basic trimming to advanced styling techniques. This course includes beard assessment, tool selection, trimming patterns, and maintenance advice for different beard types and face shapes.',
                'what_will_learn' => '<ul><li>Beard assessment and consultation</li><li>Advanced trimming techniques</li><li>Face shape compatibility</li><li>Maintenance and aftercare</li></ul>',
                'requirements' => '<ul><li>Basic understanding of facial hair</li><li>Beard trimming tools</li></ul>',
                'category_id' => $beardCategory->id,
                'level' => 'beginner',
                'language' => 'english',
                'enable_drip_content' => false,
                'price' => 39.99,
                'status' => 'published'
            ]
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
