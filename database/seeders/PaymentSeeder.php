<?php

namespace Database\Seeders;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $bootcamps = Bootcamp::where('status', 'publish')->get();
        $courses = Course::where('status', 'published')->paid()->get();

        if ($students->isEmpty()) {
            $this->command->warn('No student users found. Skipping Payment seeding.');
            return;
        }

        if ($bootcamps->isEmpty() && $courses->isEmpty()) {
            $this->command->warn('No published bootcamps or paid courses found. Skipping Payment seeding.');
            return;
        }

        $this->seedBootcampPayments($students, $bootcamps);
        $this->seedCoursePayments($students, $courses);
    }

    private function seedBootcampPayments($students, $bootcamps): void
    {
        if ($bootcamps->isEmpty()) {
            return;
        }

        foreach ($bootcamps as $bootcamp) {
            $enrolledStudents = $students->random(min(rand(2, 4), $students->count()));

            foreach ($enrolledStudents as $student) {
                $existingPayment = Payment::where('user_id', $student->id)
                    ->where('payable_type', Bootcamp::class)
                    ->where('payable_id', $bootcamp->id)
                    ->exists();

                if (!$existingPayment) {
                    $isPaid = fake()->boolean(80);

                    $payment = Payment::create([
                        'user_id' => $student->id,
                        'user_name' => $student->name,
                        'payable_type' => Bootcamp::class,
                        'payable_id' => $bootcamp->id,
                        'payment_code' => 'PAY-' . strtoupper(uniqid()),
                        'payment_method' => 'midtrans',
                        'amount' => $bootcamp->price,
                        'tax' => 0,
                        'discount' => 0,
                        'total' => $bootcamp->price,
                        'status' => $isPaid ? 'paid' : 'pending',
                        'payment_url' => $isPaid ? null : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
                        'midtrans_transaction_id' => $isPaid ? 'TRX-' . strtoupper(uniqid()) : null,
                        'paid_at' => $isPaid ? fake()->dateTimeBetween('-30 days', 'now') : null,
                        'expired_at' => $isPaid ? null : now()->addHours(24),
                    ]);

                    if ($isPaid) {
                        $bootcamp->decrement('seat_available');
                    }
                }
            }
        }
    }

    private function seedCoursePayments($students, $courses): void
    {
        if ($courses->isEmpty()) {
            return;
        }

        foreach ($courses as $course) {
            $enrolledStudents = $students->random(min(rand(1, 3), $students->count()));

            foreach ($enrolledStudents as $student) {
                $existingPayment = Payment::where('user_id', $student->id)
                    ->where('payable_type', Course::class)
                    ->where('payable_id', $course->id)
                    ->exists();

                if (!$existingPayment) {
                    $isPaid = fake()->boolean(75);

                    Payment::create([
                        'user_id' => $student->id,
                        'user_name' => $student->name,
                        'payable_type' => Course::class,
                        'payable_id' => $course->id,
                        'payment_code' => 'PAY-' . strtoupper(uniqid()),
                        'payment_method' => 'midtrans',
                        'amount' => $course->price,
                        'tax' => 0,
                        'discount' => 0,
                        'total' => $course->price,
                        'status' => $isPaid ? 'paid' : 'pending',
                        'payment_url' => $isPaid ? null : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
                        'midtrans_transaction_id' => $isPaid ? 'TRX-' . strtoupper(uniqid()) : null,
                        'paid_at' => $isPaid ? fake()->dateTimeBetween('-30 days', 'now') : null,
                        'expired_at' => $isPaid ? null : now()->addHours(24),
                    ]);
                }
            }
        }
    }
}
