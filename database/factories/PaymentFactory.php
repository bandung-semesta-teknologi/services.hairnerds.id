<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(50000, 500000);

        return [
            'user_id' => User::factory(),
            'user_name' => $this->faker->name,
            'payable_type' => Course::class,
            'payable_id' => Course::factory(),
            'payment_method' => 'midtrans',
            'payment_url' => $this->faker->url,
            'amount' => $amount,
            'tax' => 0,
            'discount' => 0,
            'total' => $amount,
            'status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'expired']),
            'paid_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
            'expired_at' => $this->faker->dateTimeBetween('now', '+1 day'),
        ];
    }

    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function paid()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'midtrans_transaction_id' => 'TXN-' . strtoupper(uniqid()),
        ]);
    }

    public function failed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }

    public function expired()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'paid_at' => null,
            'expired_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    public function forBootcamp()
    {
        return $this->state(fn (array $attributes) => [
            'payable_type' => 'App\Models\Bootcamp',
            'payable_id' => \App\Models\Bootcamp::factory(),
        ]);
    }
}
