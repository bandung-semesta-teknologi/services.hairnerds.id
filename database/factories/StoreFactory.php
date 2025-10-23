<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'store_name' => $this->faker->city(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'picture' => $this->faker->image('public/storage/stores', 400, 400, null, false),
            'website' => $this->faker->url(),
            'id_owner' => Str::uuid(),
            'social_facebook' => $this->faker->url(),
            'social_instagram' => $this->faker->url(),
            'social_twitter' => $this->faker->url(),
            'is_active' => $this->faker->boolean(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'delivery_charge' => $this->faker->randomFloat(2, 0, 50),
        ];
    }
}
