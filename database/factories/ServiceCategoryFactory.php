<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceCategoryFactory extends Factory
{
    public function definition(): array
    {
        $names = [
            'Classic Haircut', 'Fade & Taper', 'Beard Grooming', 
            'Hair Coloring', 'Scalp Treatment', 'Massage & Relax', 'Premium Package'
        ];

        return [
            'name_category'      => $this->faker->unique()->randomElement($names),
            'gender'             => $this->faker->randomElement([1, 2, 3]),
            'status'             => 1,
            'sequence'           => $this->faker->unique()->numberBetween(1, 20),
            'image'              => $this->faker->randomElement([
                'classic_haircut.png', 'fade_taper.png', 'beard_grooming.png', 
                'hair_coloring.png', 'treatment.png'
            ]),
            'id_store'           => $this->faker->numberBetween(2, 7),
            'is_recommendation'  => $this->faker->boolean(),
            'is_distance_matter' => $this->faker->boolean(),
        ];
    }
}
