<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CatalogCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_name' => ucfirst($this->faker->unique()->words(2, true)),
            'picture' => $this->faker->randomElement([
                'classic_haircut.png',
                'modern_style.png',
                'beard_trim.png',
                'kids_haircut.png',
                'hair_coloring.png',
                'hair_treatment.png',
                'massage_relax.png',
                'premium_package.png',
            ]),
        ];
    }
}
