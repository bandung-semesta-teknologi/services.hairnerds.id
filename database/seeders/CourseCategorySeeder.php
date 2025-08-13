<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Seeder;

class CourseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Basic Haircut Techniques',
            'Advanced Styling',
            'Beard Grooming',
            'Hair Washing & Treatment',
            'Fade Techniques',
            'Business Skills',
            'Customer Service',
            'Scissor Techniques',
            'Clipper Mastery',
            'Hair Color & Highlights',
            'Salon Management',
            'Product Knowledge'
        ];

        foreach ($categories as $category) {
            CourseCategory::create([
                'name' => $category
            ]);
        }
    }
}
