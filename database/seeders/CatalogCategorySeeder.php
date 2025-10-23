<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CatalogCategory;

class CatalogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Classic Haircut',     'picture' => 'classic_haircut.png'],
            ['category_name' => 'Modern Style',        'picture' => 'modern_style.png'],
            ['category_name' => 'Beard Trim',          'picture' => 'beard_trim.png'],
            ['category_name' => 'Kids Haircut',        'picture' => 'kids_haircut.png'],
            ['category_name' => 'Hair Coloring',       'picture' => 'hair_coloring.png'],
            ['category_name' => 'Hair Treatment',      'picture' => 'hair_treatment.png'],
            ['category_name' => 'Massage & Relax',     'picture' => 'massage_relax.png'],
            ['category_name' => 'Premium Package',     'picture' => 'premium_package.png'],
        ];

        foreach ($categories as $data) {
            CatalogCategory::create($data);
        }
    }
}
