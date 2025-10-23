<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Service;
use App\Models\Barber;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceBarber>
 */
class ServiceBarberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_service' => Service::inRandomOrder()->value('id') ?? Service::factory(),
            'id_barber' => Barber::inRandomOrder()->value('id') ?? Barber::factory(),
            'price' => $this->faker->numberBetween(50000, 150000),
            'weekend_price' => $this->faker->numberBetween(60000, 160000),
            'status' => $this->faker->randomElement([0, 1]),
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}
