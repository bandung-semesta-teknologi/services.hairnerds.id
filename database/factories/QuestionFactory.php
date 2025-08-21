<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $types = ['single_choice', 'multiple_choice', 'fill_blank'];
        $selectedType = $this->faker->randomElement($types);

        $questions = [
            'single_choice' => [
                'What is the most important tool for a barber?',
                'Which technique is best for creating a fade?',
                'What is the proper angle for scissor cutting?',
                'Which hair type requires special handling?'
            ],
            'multiple_choice' => [
                'Which of the following are basic barbering tools?',
                'What are the key principles of customer service?',
                'Which safety measures should always be followed?',
                'What are the different types of hair textures?'
            ],
            'fill_blank' => [
                'The proper disinfection time for tools is _____ minutes.',
                'A fade should blend from _____ to _____ length.',
                'The recommended angle for scissor cutting is _____ degrees.',
                'Hair should be washed with _____ water temperature.'
            ]
        ];

        return [
            'quiz_id' => Quiz::factory(),
            'type' => $selectedType,
            'question' => $this->faker->randomElement($questions[$selectedType]),
            'score' => $this->faker->numberBetween(5, 20),
        ];
    }

    public function singleChoice()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'single_choice',
            'question' => 'What is the most important tool for a barber?',
        ]);
    }

    public function multipleChoice()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiple_choice',
            'question' => 'Which of the following are basic barbering tools?',
        ]);
    }

    public function fillBlank()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fill_blank',
            'question' => 'The proper disinfection time for tools is _____ minutes.',
        ]);
    }
}
