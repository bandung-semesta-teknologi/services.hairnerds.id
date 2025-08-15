<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {

        Course::factory()
            ->count(1)
            ->published()
            ->beginner()
            ->create();
    }
}
