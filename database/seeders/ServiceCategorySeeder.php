<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Basic Haircut',           1, 1, 1,  'basic_haircut.png',           2, 1, false],
            ['Beard & Grooming',        1, 1, 2,  'beard_grooming.png',          2, 0, false],
            ['Color & Styling',         3, 1, 3,  'color_styling.png',           3, 1, false],
            ['Treatment & Relaxation',  3, 1, 4,  'treatment_relax.png',         3, 1, true],
            ['Premium Package',         3, 1, 5,  'premium_package.png',         4, 1, true],
            ['Ladies Styling',          2, 1, 6,  'ladies_styling.png',          4, 0, false],
            ['Kids & Family',           3, 1, 7,  'kids_family.png',             5, 0, false],
        ];

        foreach ($categories as $c) {
            ServiceCategory::create([
                'name_category'       => $c[0],
                'gender'              => $c[1],
                'status'              => $c[2],
                'sequence'            => $c[3],
                'image'               => $c[4],
                'id_store'            => $c[5],
                'is_recommendation'   => $c[6],
                'is_distance_matter'  => $c[7],
            ]);
        }
    }
}
