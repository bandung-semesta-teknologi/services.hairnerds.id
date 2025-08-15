<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFaqFactory extends Factory
{
    protected $model = CourseFaq::class;

    public function definition(): array
    {
        $questions = [
            'Who is this course for?',
            'Do I need any prior experience?',
            'What tools do I need?',
            'How long does it take to complete?',
            'Will I get a certificate?',
            'Can I get a refund?',
            'Is there ongoing support?',
            'What happens after completion?'
        ];

        return [
            'course_id' => Course::factory(),
            'question' => $this->faker->randomElement($questions),
            'answer' => $this->faker->paragraph(3),
        ];
    }
}
