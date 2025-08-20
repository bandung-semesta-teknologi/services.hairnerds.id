<?php

namespace Database\Factories;

use App\Models\AnswerBank;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerBankFactory extends Factory
{
    protected $model = AnswerBank::class;

    public function definition(): array
    {
        $answers = [
            'single_choice' => [
                'Scissors',
                'Clippers',
                'Comb',
                'Razor',
                'Hair dryer'
            ],
            'multiple_choice' => [
                'Scissors',
                'Clippers',
                'Comb',
                'Razor',
                'Hair dryer',
                'Cape',
                'Brush'
            ],
            'fill_blank' => [
                '10',
                '15',
                '20',
                '30',
                '45'
            ]
        ];

        $questionTypes = ['single_choice', 'multiple_choice', 'fill_blank'];
        $selectedType = $this->faker->randomElement($questionTypes);

        return [
            'question_id' => Question::factory(),
            'answer' => $this->faker->randomElement($answers[$selectedType]),
            'is_true' => $this->faker->boolean(25),
        ];
    }

    public function correct()
    {
        return $this->state(fn (array $attributes) => [
            'is_true' => true,
        ]);
    }

    public function incorrect()
    {
        return $this->state(fn (array $attributes) => [
            'is_true' => false,
        ]);
    }

    public function forSingleChoice()
    {
        return $this->state(fn (array $attributes) => [
            'answer' => $this->faker->randomElement(['Scissors', 'Clippers', 'Comb', 'Razor']),
        ]);
    }

    public function forMultipleChoice()
    {
        return $this->state(fn (array $attributes) => [
            'answer' => $this->faker->randomElement(['Scissors', 'Clippers', 'Comb', 'Razor', 'Cape', 'Brush']),
        ]);
    }

    public function forFillBlank()
    {
        return $this->state(fn (array $attributes) => [
            'answer' => $this->faker->randomElement(['10', '15', '20', '30']),
        ]);
    }
}
