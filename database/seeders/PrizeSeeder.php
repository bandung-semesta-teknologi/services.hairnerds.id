<?php

namespace Database\Seeders;

use App\Models\Prize;
use Illuminate\Database\Seeder;

class PrizeSeeder extends Seeder
{
    public function run(): void
    {
        Prize::factory()->count(1)->active()->promoCode()->create();
        Prize::factory()->count(1)->active()->physical()->create();
        Prize::factory()->count(1)->inactive()->create();
    }
}
