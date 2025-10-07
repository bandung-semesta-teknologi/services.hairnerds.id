<?php

namespace Database\Factories;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        $questions = [
            'Who is this for?',
            'Do I need any prior experience?',
            'What tools do I need?',
            'How long does it take to complete?',
            'Will I get a certificate?',
            'Can I get a refund?',
            'Is there ongoing support?',
            'What happens after completion?'
        ];

        return [
            'faqable_type' => Course::class,
            'faqable_id' => Course::factory(),
            'question' => $this->faker->randomElement($questions),
            'answer' => $this->faker->paragraph(3),
        ];
    }

    public function forCourse()
    {
        return $this->state(fn (array $attributes) => [
            'faqable_type' => Course::class,
            'faqable_id' => Course::factory(),
        ]);
    }

    public function forBootcamp()
    {
        return $this->state(fn (array $attributes) => [
            'faqable_type' => Bootcamp::class,
            'faqable_id' => Bootcamp::factory(),
        ]);
    }
}
