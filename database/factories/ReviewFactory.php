<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $positiveComments = [
            'Excellent course! Very comprehensive and well-structured.',
            'Great instructor, learned a lot from this course.',
            'Highly recommend this course to anyone interested in the topic.',
            'Amazing content and practical examples.',
            'Best course I\'ve taken so far!',
            'Clear explanations and good pacing.',
            'Very helpful and informative course.',
            'Outstanding quality and depth of content.'
        ];

        $neutralComments = [
            'Good course overall, but could use more examples.',
            'Decent content, some sections were better than others.',
            'Average course, met my basic expectations.',
            'Okay course, but I\'ve seen better.',
            'Not bad, but could be improved in some areas.',
            'Adequate information provided.'
        ];

        $negativeComments = [
            'Course was too basic for my level.',
            'Expected more from this course.',
            'Could be better organized.',
            'Some parts were confusing.',
            'Not worth the price.',
            'Disappointed with the content quality.'
        ];

        $rating = $this->faker->numberBetween(1, 5);

        $comments = match(true) {
            $rating >= 4 => $this->faker->randomElement($positiveComments),
            $rating == 3 => $this->faker->randomElement($neutralComments),
            default => $this->faker->randomElement($negativeComments)
        };

        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'comments' => $comments,
            'rating' => $rating,
            'is_visible' => $this->faker->boolean(90),
        ];
    }

    public function positive()
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(4, 5),
        ]);
    }

    public function negative()
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(1, 2),
        ]);
    }

    public function visible()
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => true,
        ]);
    }

    public function hidden()
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
