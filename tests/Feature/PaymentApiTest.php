<?php

use App\Models\Bootcamp;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('payment crud api', function () {
    beforeEach(function () {
        $this->admin = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'admin']);

        $this->instructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->otherStudent = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->category = Category::factory()->create();

        $this->paidCourse = Course::factory()->published()->create(['price' => 100000]);
        $this->paidCourse->categories()->attach($this->category->id);
        $this->paidCourse->instructors()->attach($this->instructor->id);

        $this->freeCourse = Course::factory()->published()->create(['price' => 0]);
        $this->freeCourse->categories()->attach($this->category->id);
        $this->freeCourse->instructors()->attach($this->instructor->id);

        $this->bootcamp = Bootcamp::factory()->published()->create([
            'user_id' => $this->instructor->id,
            'price' => 500000,
            'seat_available' => 10
        ]);
        $this->bootcamp->categories()->attach($this->category->id);
    });

    describe('guest access', function () {
        it('guest cannot access payments', function () {
            getJson('/api/payments')
                ->assertUnauthorized();
        });

        it('guest cannot create payment', function () {
            postJson("/api/courses/{$this->paidCourse->id}/payment")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all payments', function () {
            Payment::factory()->count(3)->paid()->create([
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            Payment::factory()->count(2)->pending()->create([
                'user_id' => $this->student->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson('/api/payments')
                ->assertOk()
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'user_name',
                            'payable_type',
                            'payable_id',
                            'payment_code',
                            'payment_method',
                            'amount',
                            'total',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can filter payments by status', function () {
            Payment::factory()->count(2)->paid()->create();
            Payment::factory()->count(3)->pending()->create();

            getJson('/api/payments?status=paid')
                ->assertOk()
                ->assertJsonCount(2, 'data');

            getJson('/api/payments?status=pending')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('admin can view any payment details', function () {
            $payment = Payment::factory()->paid()->create([
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson("/api/payments/{$payment->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $payment->id)
                ->assertJsonPath('data.status', 'paid');
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can only see their own payments', function () {
            Payment::factory()->count(2)->create([
                'user_id' => $this->student->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            Payment::factory()->count(3)->create([
                'user_id' => $this->otherStudent->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson('/api/payments')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('student can create payment for paid course', function () {
            postJson("/api/courses/{$this->paidCourse->id}/payment")
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Payment created successfully')
                ->assertJsonStructure([
                    'data' => [
                        'payment' => [
                            'id',
                            'user_id',
                            'payable_type',
                            'payable_id',
                            'amount',
                            'total',
                            'status'
                        ],
                        'snap_token',
                        'redirect_url'
                    ]
                ]);

            $this->assertDatabaseHas('payments', [
                'user_id' => $this->student->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id,
                'amount' => 100000,
                'status' => 'pending'
            ]);
        });

        it('student cannot create payment for free course', function () {
            postJson("/api/courses/{$this->freeCourse->id}/payment")
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Course is free, no payment required');
        });

        it('student cannot create payment for already enrolled course', function () {
            Enrollment::create([
                'user_id' => $this->student->id,
                'course_id' => $this->paidCourse->id
            ]);

            postJson("/api/courses/{$this->paidCourse->id}/payment")
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Already enrolled in this course');
        });

        it('student cannot create duplicate pending payment', function () {
            Payment::factory()->pending()->create([
                'user_id' => $this->student->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            postJson("/api/courses/{$this->paidCourse->id}/payment")
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Pending payment already exists for this course');
        });

        it('student can create payment for bootcamp', function () {
            postJson("/api/bootcamps/{$this->bootcamp->id}/payment")
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Payment created successfully');

            $this->assertDatabaseHas('payments', [
                'user_id' => $this->student->id,
                'payable_type' => Bootcamp::class,
                'payable_id' => $this->bootcamp->id,
                'amount' => 500000,
                'status' => 'pending'
            ]);
        });

        it('student cannot create payment for bootcamp with no available seats', function () {
            $this->bootcamp->update(['seat_available' => 0]);

            postJson("/api/bootcamps/{$this->bootcamp->id}/payment")
                ->assertUnprocessable()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'No seats available for this bootcamp');
        });

        it('student can view their own payment', function () {
            $payment = Payment::factory()->create([
                'user_id' => $this->student->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson("/api/payments/{$payment->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $payment->id)
                ->assertJsonPath('data.user_id', $this->student->id);
        });

        it('student cannot view other student payment', function () {
            $payment = Payment::factory()->create([
                'user_id' => $this->otherStudent->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson("/api/payments/{$payment->id}")
                ->assertForbidden();
        });

        it('student can check payment status', function () {
            $payment = Payment::factory()->pending()->create([
                'user_id' => $this->student->id,
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson("/api/payments/{$payment->id}/status")
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor cannot view payments', function () {
            Payment::factory()->count(2)->create([
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson('/api/payments')
                ->assertForbidden();
        });

        it('instructor cannot create payment', function () {
            postJson("/api/courses/{$this->paidCourse->id}/payment")
                ->assertForbidden();
        });

        it('instructor cannot view payment details', function () {
            $payment = Payment::factory()->create([
                'payable_type' => Course::class,
                'payable_id' => $this->paidCourse->id
            ]);

            getJson("/api/payments/{$payment->id}")
                ->assertForbidden();
        });
    });

    describe('payment callback', function () {
        it('handles successful payment callback', function () {
            $payment = Payment::factory()->pending()->create([
                'payment_code' => 'PAY-TEST123',
                'total' => 100000
            ]);

            $serverKey = config('services.midtrans.server_key');
            $signatureKey = hash('sha512', 'PAY-TEST123' . '200' . '100000' . $serverKey);

            postJson('/api/payments/callback', [
                'order_id' => 'PAY-TEST123',
                'status_code' => '200',
                'gross_amount' => '100000',
                'signature_key' => $signatureKey,
                'transaction_status' => 'settlement'
            ])
                ->assertOk()
                ->assertJsonPath('status', 'ok');

            $this->assertDatabaseHas('payments', [
                'payment_code' => 'PAY-TEST123',
                'status' => 'paid'
            ]);
        });

        it('handles failed payment callback', function () {
            $payment = Payment::factory()->pending()->create([
                'payment_code' => 'PAY-TEST456',
                'total' => 100000
            ]);

            $serverKey = config('services.midtrans.server_key');
            $signatureKey = hash('sha512', 'PAY-TEST456' . '400' . '100000' . $serverKey);

            postJson('/api/payments/callback', [
                'order_id' => 'PAY-TEST456',
                'status_code' => '400',
                'gross_amount' => '100000',
                'signature_key' => $signatureKey,
                'transaction_status' => 'deny'
            ])
                ->assertOk();

            $this->assertDatabaseHas('payments', [
                'payment_code' => 'PAY-TEST456',
                'status' => 'failed'
            ]);
        });

        it('rejects callback with invalid signature', function () {
            $payment = Payment::factory()->pending()->create([
                'payment_code' => 'PAY-TEST789'
            ]);

            postJson('/api/payments/callback', [
                'order_id' => 'PAY-TEST789',
                'status_code' => '200',
                'gross_amount' => '100000',
                'signature_key' => 'invalid-signature',
                'transaction_status' => 'settlement'
            ])
                ->assertForbidden()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Invalid signature');
        });
    });

    describe('payment finish', function () {
        it('returns payment details on finish', function () {
            $payment = Payment::factory()->paid()->create([
                'payment_code' => 'PAY-FINISH123'
            ]);

            getJson('/api/payments/finish?order_id=PAY-FINISH123')
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Payment process completed')
                ->assertJsonPath('data.payment_code', 'PAY-FINISH123');
        });

        it('returns error when payment not found on finish', function () {
            getJson('/api/payments/finish?order_id=NONEXISTENT')
                ->assertNotFound()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Payment not found');
        });
    });
});
