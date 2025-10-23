<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gender'            => $this->faker->randomElement([1, 2, 3]),
            'name_service'      => ucfirst($this->faker->words(2, true)),
            'service_subtitle'  => $this->faker->catchPhrase(),
            'id_category'       => $this->faker->numberBetween(1, 8),
            'description'       => $this->faker->sentence(10),
            'youtube_code'      => null,
            'price_type'        => $this->faker->randomElement([1, 2]),
            'price_description' => 'IDR ' . $this->faker->numberBetween(50000, 250000),
            'allow_visible'     => true,
            'session_duration'  => $this->faker->randomElement(['00:30:00', '00:45:00', '01:00:00']),
            'buffer_time'       => '00:10:00',
            'image'             => $this->faker->randomElement([
                'classic_haircut.png', 'modern_fade.png', 'beard_trim.png', 'hair_treatment.png'
            ]),
            'id_store'          => $this->faker->numberBetween(2, 7),
        ];
    }
}
