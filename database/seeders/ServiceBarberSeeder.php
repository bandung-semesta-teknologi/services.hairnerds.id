<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceBarber;

class ServiceBarberSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [1, 1, 50000, 60000, 1],
            [1, 2, 55000, 65000, 1],
            [2, 1, 75000, 85000, 1],
            [2, 3, 70000, 80000, 1],
            [3, 1, 90000, 100000, 1],
            [3, 2, 95000, 110000, 1],
            [4, 4, 120000, 135000, 1],
        ];

        foreach ($items as $item) {
            ServiceBarber::factory()->create([
                'id_service'     => $item[0],
                'id_barber'      => $item[1],
                'price'          => $item[2],
                'weekend_price'  => $item[3],
                'status'         => $item[4],
            ]);
        }

        ServiceBarber::factory(5)->create();
    }
}
