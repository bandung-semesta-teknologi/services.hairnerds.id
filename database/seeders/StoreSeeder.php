<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            ['store_name' => 'Kebayoran Baru',     'picture' => '1657227958.png', 'phone' => '+62 857-7217-3588', 'is_active' => true],
            ['store_name' => 'Pantai Indah Kapuk', 'picture' => '1657228051.png', 'phone' => '+6221 22570089',   'is_active' => true],
            ['store_name' => 'Bandung',            'picture' => '1665022716.png', 'phone' => '+62811-2160-042',  'is_active' => true],
            ['store_name' => 'Gading Serpong',     'picture' => '1672829432.png', 'phone' => '02159995740',      'is_active' => true],
            ['store_name' => 'Bekasi',             'picture' => '1725625967.png', 'phone' => '+6285888088083',   'is_active' => true],
            ['store_name' => 'Bintaro',            'picture' => '1725625966.png', 'phone' => '+6285888088013',   'is_active' => true],
        ];

        foreach ($stores as $store) {
            Store::create(array_merge($store, [
                'id_owner' => Str::uuid(),
                'address' => null,
                'website' => null,
                'social_facebook' => null,
                'social_instagram' => null,
                'social_twitter' => null,
                'latitude' => null,
                'longitude' => null,
                'delivery_charge' => 0,
            ]));
        }
    }
}
