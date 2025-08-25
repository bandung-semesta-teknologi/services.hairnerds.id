<?php

namespace Database\Factories;

use App\Models\Bootcamp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BootcampFactory extends Factory
{
    protected $model = Bootcamp::class;

    public function definition(): array
    {
        $titles = [
            'Professional Barbering Intensive Course',
            'Classic Scissor Cutting Masterclass',
            'Modern Fade Techniques Workshop',
            'Beard Grooming & Styling Bootcamp',
            'Hair Color & Highlights Training',
            'Advanced Clipper Techniques',
            'Traditional Wet Shaving Workshop',
            'Men\'s Hair Styling Bootcamp',
            'Barbershop Business Management',
            'Complete Barber Certification Program'
        ];

        $locations = [
            'Hairnerds Academy Jakarta',
            'Hairnerds Studio Bandung',
            'Hairnerds Training Center Surabaya',
            'Hairnerds Workshop Yogyakarta',
            'Hairnerds Institute Medan',
            'Hairnerds Flagship Store, Kemang'
        ];

        $startDate = $this->faker->dateTimeBetween('now', '+6 months');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +7 days');

        $totalSeat = $this->faker->numberBetween(10, 30);
        $blockedSeat = $this->faker->numberBetween(0, 3);
        $availableSeat = $totalSeat - $blockedSeat;

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement($titles),
            'start_at' => $startDate,
            'end_at' => $endDate,
            'seat' => $totalSeat,
            'seat_available' => $availableSeat,
            'seat_blocked' => $blockedSeat,
            'description' => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->paragraph(2),
            'status' => $this->faker->randomElement(['draft', 'publish', 'unpublish', 'rejected']),
            'price' => $this->faker->numberBetween(1000000, 15000000),
            'location' => $this->faker->randomElement($locations),
            'contact_person' => $this->faker->name(),
            'url_location' => $this->faker->optional(0.7)->url(),
            'verified_at' => null,
        ];
    }

    public function published()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'publish',
            'verified_at' => now(),
        ]);
    }

    public function draft()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'verified_at' => null,
        ]);
    }

    public function unpublished()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unpublish',
            'verified_at' => now(),
        ]);
    }

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'verified_at' => now(),
        ]);
    }

    public function verified()
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => now(),
        ]);
    }
}
