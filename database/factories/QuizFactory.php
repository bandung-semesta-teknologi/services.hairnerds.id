<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        $titles = [
            'Basic Knowledge Assessment',
            'Mid-Course Evaluation',
            'Skills Assessment Quiz',
            'Final Knowledge Test',
            'Practice Quiz',
            'Technique Evaluation',
            'Safety and Hygiene Quiz',
            'Tool Knowledge Test',
            'Customer Service Assessment',
            'Certification Exam'
        ];

        $instructions = [
            'Read each question carefully and select the best answer.',
            'Answer all questions to complete the quiz.',
            'You have limited time to complete this assessment.',
            'Choose the most appropriate answer for each question.',
            'This quiz tests your understanding of the material covered.'
        ];

        return [
            'section_id' => Section::factory(),
            'lesson_id' => Lesson::factory(),
            'course_id' => Course::factory(),
            'title' => $this->faker->randomElement($titles),
            'instruction' => $this->faker->randomElement($instructions),
            'duration' => $this->faker->time('H:i:s', '02:00:00'),
            'total_marks' => $this->faker->numberBetween(50, 200),
            'pass_marks' => function (array $attributes) {
                return (int) ($attributes['total_marks'] * 0.6);
            },
            'max_retakes' => $this->faker->numberBetween(1, 5),
            'min_lesson_taken' => $this->faker->numberBetween(0, 5),
        ];
    }

    public function beginner()
    {
        return $this->state(fn (array $attributes) => [
            'total_marks' => $this->faker->numberBetween(50, 100),
            'duration' => '00:30:00',
            'max_retakes' => 3,
        ]);
    }

    public function advanced()
    {
        return $this->state(fn (array $attributes) => [
            'total_marks' => $this->faker->numberBetween(150, 200),
            'duration' => '02:00:00',
            'max_retakes' => 1,
        ]);
    }
}
