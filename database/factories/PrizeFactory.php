<?php

namespace Database\Factories;

use App\Models\Prize;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PrizeFactory extends Factory
{
    protected $model = Prize::class;

    public function definition(): array
    {
        $prizeNames = [
            'Free Haircut Voucher',
            'Premium Styling Package',
            'Hair Treatment Discount 50%',
            'Beard Grooming Service',
            'Exclusive Membership Card',
            'Hairnerds Merchandise Set',
            'VIP Salon Experience',
            'Hair Color Service',
            'Deluxe Grooming Kit',
            'Special Event Ticket',
            'Classic Shave Package',
            'Student Discount Voucher',
            'Birthday Special Promo',
            'Loyalty Reward Points',
            'Haircut & Style Combo',
            'Premium Hair Wax Set',
            'Professional Scissors Kit',
            'Barbershop Gift Card',
            'Monthly Membership Pass',
            'Hairnerds T-Shirt',
        ];

        $name = fake()->unique()->randomElement($prizeNames);

        $type = fake()->randomElement(['physical', 'promo_code']);
        $totalStock = fake()->numberBetween(50, 500);
        $usedStock = fake()->numberBetween(0, (int)($totalStock * 0.2));
        $blockedStock = fake()->numberBetween(0, (int)($totalStock * 0.1));
        $availableStock = $totalStock - $usedStock - $blockedStock;

        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s').' +3 months');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(3),
            'type' => $type,
            'point_cost' => fake()->numberBetween(100, 1000),
            'total_stock' => $totalStock,
            'available_stock' => $availableStock,
            'blocked_stock' => $blockedStock,
            'used_stock' => $usedStock,
            'redemption_start_date' => $startDate,
            'redemption_end_date' => $endDate,
            'status' => 'active',
            'banner_image' => null,
            'created_by' => fake()->name(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'physical',
        ]);
    }

    public function promoCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'promo_code',
        ]);
    }
}
