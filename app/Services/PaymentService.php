<?php

namespace App\Services;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function createCoursePayment(Course $course, User $user)
    {
        if ($course->isFree()) {
            throw new \Exception('Course is free, no payment required');
        }

        if ($course->status !== 'published') {
            throw new \Exception('Cannot purchase unpublished course');
        }

        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($existingEnrollment) {
            throw new \Exception('Already enrolled in this course');
        }

        $existingPendingPayment = Payment::where('user_id', $user->id)
            ->where('payable_type', Course::class)
            ->where('payable_id', $course->id)
            ->pending()
            ->exists();

        if ($existingPendingPayment) {
            throw new \Exception('Pending payment already exists for this course');
        }

        return DB::transaction(function () use ($course, $user) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'payable_type' => Course::class,
                'payable_id' => $course->id,
                'payment_method' => 'midtrans',
                'amount' => $course->price,
                'total' => $course->price,
                'expired_at' => now()->addHours(24),
            ]);

            $midtransResponse = $this->midtransService->createTransaction($payment);

            return [
                'payment' => $payment,
                'snap_token' => $midtransResponse['token'] ?? null,
                'redirect_url' => $midtransResponse['redirect_url'] ?? null,
            ];
        });
    }

    public function createBootcampPayment(Bootcamp $bootcamp, User $user)
    {
        if ($bootcamp->price === 0) {
            throw new \Exception('Bootcamp is free, no payment required');
        }

        if ($bootcamp->status !== 'publish') {
            throw new \Exception('Cannot purchase unpublished bootcamp');
        }

        if ($bootcamp->seat_available <= 0) {
            throw new \Exception('No seats available for this bootcamp');
        }

        $existingPendingPayment = Payment::where('user_id', $user->id)
            ->where('payable_type', Bootcamp::class)
            ->where('payable_id', $bootcamp->id)
            ->pending()
            ->exists();

        if ($existingPendingPayment) {
            throw new \Exception('Pending payment already exists for this bootcamp');
        }

        return DB::transaction(function () use ($bootcamp, $user) {
            if ($bootcamp->fresh()->seat_available <= 0) {
                throw new \Exception('No seats available for this bootcamp');
            }

            $payment = Payment::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'payable_type' => Bootcamp::class,
                'payable_id' => $bootcamp->id,
                'payment_method' => 'midtrans',
                'amount' => $bootcamp->price,
                'total' => $bootcamp->price,
                'expired_at' => now()->addHours(24),
            ]);

            $midtransResponse = $this->midtransService->createTransaction($payment);

            return [
                'payment' => $payment,
                'snap_token' => $midtransResponse['token'] ?? null,
                'redirect_url' => $midtransResponse['redirect_url'] ?? null,
            ];
        });
    }

    public function handlePaymentSuccess(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($payment) {
            $payment->markAsPaid();

            $payable = $payment->payable;

            if ($payable instanceof Course) {
                $this->handleCourseEnrollment($payment, $payable);
            } elseif ($payable instanceof Bootcamp) {
                $this->handleBootcampEnrollment($payment, $payable);
            }
        });
    }

    protected function handleCourseEnrollment(Payment $payment, Course $course)
    {
        $existingEnrollment = Enrollment::where('user_id', $payment->user_id)
            ->where('course_id', $course->id)
            ->exists();

        if (!$existingEnrollment) {
            Enrollment::create([
                'user_id' => $payment->user_id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
            ]);
        }
    }

    protected function handleBootcampEnrollment(Payment $payment, Bootcamp $bootcamp)
    {
        $bootcamp->decrement('seat_available');

        Log::info('Bootcamp seat decremented', [
            'bootcamp_id' => $bootcamp->id,
            'remaining_seats' => $bootcamp->fresh()->seat_available,
            'payment_id' => $payment->id,
        ]);
    }

    public function handlePaymentFailure(Payment $payment, $reason = null)
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->markAsFailed(['reason' => $reason]);
    }

    public function handlePaymentExpired(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->markAsExpired();
    }
}
