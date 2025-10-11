<?php

namespace Database\Factories;

use App\Models\Bootcamp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BootcampFactory extends Factory
{
    protected $model = Bootcamp::class;

    public function definition(): array
    {
        $title = fake()->randomElement([
            'Professional Barbering Intensive Course',
            'Advanced Clipper Techniques',
            'Hair Color & Highlights Training',
            'Beard Grooming & Styling Bootcamp',
            'Men\'s Hair Styling Bootcamp',
            'Classic Scissor Cutting Masterclass',
            'Modern Fade Techniques Workshop',
            'Traditional Wet Shaving Workshop',
            'Barbershop Business Management',
            'Complete Barber Certification Program',
        ]);

        $location = fake()->randomElement([
            'Hairnerds Academy Jakarta',
            'Hairnerds Training Center Surabaya',
            'Hairnerds Institute Medan',
            'Hairnerds Studio Bandung',
            'Hairnerds Workshop Yogyakarta',
            'Hairnerds Flagship Store, Kemang',
        ]);

        $startAt = fake()->dateTimeBetween('now', '+6 months');
        $endAt = fake()->dateTimeBetween($startAt, $startAt->format('Y-m-d H:i:s').' +7 days');

        $seat = fake()->numberBetween(10, 30);
        $seatBlocked = fake()->numberBetween(0, 3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(15),
            'location' => $location,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'price' => fake()->numberBetween(1000000, 15000000),
            'seat' => $seat,
            'seat_available' => $seat - $seatBlocked - fake()->numberBetween(0, 3),
            'seat_blocked' => $seatBlocked,
            'contact_person' => fake()->name(),
            'url_location' => fake()->optional()->url(),
            'status' => 'draft',
            'verified_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'publish',
            'verified_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'verified_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'verified_at' => null,
        ]);
    }
}
