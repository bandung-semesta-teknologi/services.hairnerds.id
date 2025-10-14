<?php

namespace Database\Seeders;

use App\Enums\MembershipType;
use App\Models\MembershipSerial;
use Illuminate\Database\Seeder;

class MembershipSerialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $type = $i % 3 === 0 ? MembershipType::Premium : MembershipType::Regular;

            // Retry logic to handle potential duplicates
            $maxAttempts = 10;
            $inserted = false;

            for ($attempt = 0; $attempt < $maxAttempts && !$inserted; $attempt++) {
                try {
                    MembershipSerial::create([
                        'serial_number' => $this->generateSerial($type),
                        'type' => $type,
                    ]);
                    $inserted = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    // If duplicate, retry with a new serial
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Create a numeric prefix from type to preserve original intent
     * premium => 100, reguler => 1
     */
    private function typePrefix(string $type): string
    {
        return $type === 'premium' ? '100' : '1';
    }

    /**
     * Luhn check digit for a numeric string base (return 0-9)
     */
    private function luhnChecksumDigit(string $base): int
    {
        $sum = 0;
        $reverse = strrev($base);

        for ($i = 0; $i < strlen($reverse); $i++) {
            $d = (int)$reverse[$i];
            if ($i % 2 === 0) {
                // Double every second digit starting from index 0 in reversed order
                $d *= 2;
                if ($d > 9) $d -= 9;
            }
            $sum += $d;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Generate a 16-digit serial: [prefix][yymm][random][luhn]
     */
    private function generateSerial(MembershipType $type): string
    {
        $prefix = $this->typePrefix($type->value);
        $datePart = date('ym'); // yymm format (e.g., "2510" for Oct 2025)
        $baseLen = strlen($prefix) + strlen($datePart);
        $randomLen = 15 - $baseLen; // 15 because last digit is checksum

        if ($randomLen < 1) {
            throw new \RuntimeException('Konfigurasi panjang serial tidak valid.');
        }

        $max = 10 ** $randomLen - 1;
        $randomPart = str_pad(
            (string)random_int(0, $max),
            $randomLen,
            '0',
            STR_PAD_LEFT
        );

        $base = $prefix . $datePart . $randomPart;
        $check = $this->luhnChecksumDigit($base);

        return $base . (string)$check;
    }
}
