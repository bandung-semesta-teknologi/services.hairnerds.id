<?php

namespace Database\Seeders;

use App\Models\Prize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrizeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('prizes')->truncate();

        Prize::factory()->count(5)->active()->promoCode()->create();
        Prize::factory()->count(5)->active()->physical()->create();
        Prize::factory()->count(5)->inactive()->create();

        Schema::enableForeignKeyConstraints();
    }
}
