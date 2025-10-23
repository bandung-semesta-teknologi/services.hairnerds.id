<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['Male',   'Classic Haircut',       'Clean & Simple',       1, 'Standard men haircut with finishing.', null, 1, 'IDR 80K',  true, '00:45:00', '00:10:00', 'classic_haircut.png', 2],
            ['Male',   'Modern Fade',           'Trendy Look',          2, 'Modern fade and taper with styling.', null, 1, 'IDR 100K', true, '00:50:00', '00:10:00', 'modern_fade.png',     2],
            ['Male',   'Beard Trim',            'Neat & Sharp',         3, 'Trim and shape your beard precisely.', null, 1, 'IDR 50K',  true, '00:30:00', '00:05:00', 'beard_trim.png',      3],
            ['Unisex', 'Hair Coloring',         'Express Your Style',   5, 'Full or partial hair coloring.', null, 2, 'Start from IDR 200K', true, '01:30:00', '00:15:00', 'hair_coloring.png', 4],
            ['Unisex', 'Hair Treatment',        'Healthy Hair Care',    6, 'Hair spa and repair treatment.', null, 2, 'Start from IDR 150K', true, '01:00:00', '00:10:00', 'hair_treatment.png', 4],
            ['Male',   'Massage & Relax',       'Head & Shoulder',      7, 'Relaxing massage after haircut.', null, 1, 'IDR 70K', true, '00:30:00', '00:10:00', 'massage_relax.png',    5],
            ['Unisex', 'Premium Package',       'All-in-One',           8, 'Haircut + Beard + Massage.', null, 1, 'IDR 250K', true, '02:00:00', '00:15:00', 'premium_package.png', 6],
        ];

        foreach ($services as $s) {
            Service::create([
                'gender'            => $s[0] === 'Male' ? 1 : ($s[0] === 'Female' ? 2 : 3),
                'name_service'      => $s[1],
                'service_subtitle'  => $s[2],
                'id_category'       => $s[3],
                'description'       => $s[4],
                'youtube_code'      => $s[5],
                'price_type'        => $s[6],
                'price_description' => $s[7],
                'allow_visible'     => $s[8],
                'session_duration'  => $s[9],
                'buffer_time'       => $s[10],
                'image'             => $s[11],
                'id_store'          => $s[12],
            ]);
        }
    }
}
