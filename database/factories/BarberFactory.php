<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Store;

class BarberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_store'  => Store::inRandomOrder()->value('id'),
            'email'     => $this->faker->unique()->safeEmail(),
            'full_name' => $this->faker->name(),
            'color'     => $this->faker->safeHexColor(),
            'phone'     => '62' . $this->faker->numerify('812########'),
            'is_active' => 1,
            'sync_status' => 1,
        ];
    }
}
