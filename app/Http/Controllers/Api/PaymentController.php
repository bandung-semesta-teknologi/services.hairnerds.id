<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentStoreRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $midtransService;

    public function __construct(PaymentService $paymentService, MidtransService $midtransService)
    {
        $this->paymentService = $paymentService;
        $this->midtransService = $midtransService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $user = $request->user();

        $payments = Payment::query()
            ->with(['payable'])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payable_type, fn($q) => $q->where('payable_type', $request->payable_type))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['payable']);

        return new PaymentResource($payment);
    }

    public function createCoursePayment(Course $course)
    {
        $this->authorize('create', Payment::class);

        try {
            $user = request()->user();

            $result = $this->paymentService->createCoursePayment($course, $user);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment created successfully',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'snap_token' => $result['snap_token'],
                    'redirect_url' => $result['redirect_url'],
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating course payment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function createBootcampPayment(Bootcamp $bootcamp)
    {
        $this->authorize('create', Payment::class);

        try {
            $user = request()->user();

            $result = $this->paymentService->createBootcampPayment($bootcamp, $user);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment created successfully',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'snap_token' => $result['snap_token'],
                    'redirect_url' => $result['redirect_url'],
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating bootcamp payment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function callback(Request $request)
    {
        try {
            $orderId = $request->order_id;
            $statusCode = $request->status_code;
            $grossAmount = $request->gross_amount;
            $signatureKey = $request->signature_key;

            if (!$this->midtransService->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }

            $payment = Payment::where('payment_code', $orderId)->first();

            if (!$payment) {
                return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
            }

            $transactionStatus = $request->transaction_status;
            $fraudStatus = $request->fraud_status ?? null;

            Log::info('Midtrans callback received', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus
            ]);

            switch ($transactionStatus) {
                case 'capture':
                    if ($fraudStatus === 'accept') {
                        $this->paymentService->handlePaymentSuccess($payment);
                    }
                    break;

                case 'settlement':
                    $this->paymentService->handlePaymentSuccess($payment);
                    break;

                case 'pending':
                    Log::info('Payment pending', ['order_id' => $orderId]);
                    break;

                case 'deny':
                case 'cancel':
                case 'expire':
                    if ($transactionStatus === 'expire') {
                        $this->paymentService->handlePaymentExpired($payment);
                    } else {
                        $this->paymentService->handlePaymentFailure($payment, $transactionStatus);
                    }
                    break;

                default:
                    Log::warning('Unknown transaction status', [
                        'order_id' => $orderId,
                        'status' => $transactionStatus
                    ]);
                    break;
            }

            $payment->update([
                'raw_response_midtrans' => $request->all()
            ]);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function finish(Request $request)
    {
        $orderId = $request->order_id;
        $payment = Payment::where('payment_code', $orderId)->first();

        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment process completed',
            'data' => new PaymentResource($payment->load(['payable']))
        ]);
    }

    public function checkStatus(Payment $payment)
    {
        $this->authorize('view', $payment);

        try {
            $status = $this->midtransService->getTransactionStatus($payment->payment_code);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment_status' => $payment->status,
                    'midtrans_status' => $status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    public function generateSignature(Request $request)
    {
        // Hanya untuk development/testing
        if (!app()->environment(['local', 'testing'])) {
            return response()->json(['error' => 'Not allowed in production'], 403);
        }

        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code', '200');

        if (!$orderId) {
            return response()->json(['error' => 'order_id required'], 422);
        }

        $payment = Payment::where('payment_code', $orderId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $grossAmount = $payment->total;

        $serverKey = config('services.midtrans.server_key');
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payableInfo = [
            'type' => class_basename($payment->payable_type),
            'id' => $payment->payable_id,
        ];

        if ($payment->payable) {
            $payableInfo['title'] = $payment->payable->title ?? 'N/A';
            $payableInfo['price'] = $payment->payable->price ?? 0;
        }

        return response()->json([
            'id' => $payment->id,
            'order_id' => $orderId,
            'user_name' => $payment->user_name,
            'payable' => $payableInfo,
            'status' => $payment->status,
            'gross_amount' => (string) $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_time' => now()->format('Y-m-d H:i:s')
        ]);
    }
}
