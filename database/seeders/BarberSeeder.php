<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barber;
use App\Models\User;

class BarberSeeder extends Seeder
{
    public function run(): void
    {
        $barbers = [
            [51, 'akearaboy@gmail.com', 'Gunz', 2, '#b2ff66', '6281234567801'],
            [53, 'aldyarief530@gmail.com', 'Aries', 2, '#66ffb2', '6281234567802'],
            [59, 'ramaboyss08@gmail.com', 'Rama', 2, '#831100', '6281234567803'],
            [68, 'BUSINESS.ALBASITHS@GMAIL.COM', 'Al Basith', 3, '#00ff23', '6281234567804'],
            [73, 'its.adityanugraha@gmail.com', 'Adit', 3, '#ff6600', '6281234567805'],
            [86, 'mbaharief001@gmail.com', 'Mbah Arief', 3, '#ffb6c1', '6281234567806'],
            [105, 'Doddis192@gmail.com', 'Doddi', 4, '#fefb41', '6281234567807'],
            [107, 'hendra.sitwin@gmail.com', 'Hendra', 4, '#982abc', '6281234567808'],
            [104, 'agungpe.7@gmail.com', 'Agung', 4, '#05fffb', '6281234567809'],
            [123, 'satriandkputra@gmail.com', 'Satria', 5, '#efcaff', '6281234567810'],
            [122, 'Susiloariwibowo8@gmail.com', 'Ari', 5, '#285ff4', '6281234567811'],
            [121, 'erwinwahyuuu62@gmail.com', 'Erwin', 5, '#886630', '6281234567812'],
            [152, 'dekha@hairnerds.id', 'Dekha', 6, '#8c00ff', '6281234567813'],
            [149, 'acoeb@hairnerds.id', 'Acoeb', 6, '#ff2929', '6281234567814'],
            [159, 'nurdin@hairnerds.id', 'Nurdin', 6, '#87a040', '6281234567815'],
        ];

        $existingUserIds = User::pluck('id')->toArray();

        foreach ($barbers as $data) {
            $randomUserId = count($existingUserIds) > 0
                ? $existingUserIds[array_rand($existingUserIds)]
                : null;

            Barber::create([
                'id_user'   => $randomUserId,
                'id_store'  => $data[3],
                'email'     => $data[1],
                'full_name' => $data[2],
                'color'     => $data[4],
                'phone'     => $data[5],
                'is_active' => 1,
                'sync_status' => 1,
            ]);
        }
    }
}
