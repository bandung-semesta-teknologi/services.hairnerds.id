<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected $serverKey;
    protected $clientKey;
    protected $isProduction;
    protected $snapUrl;
    protected $apiUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->clientKey = config('services.midtrans.client_key');
        $this->isProduction = config('services.midtrans.is_production');

        $this->snapUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $this->apiUrl = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function createTransaction(Payment $payment)
    {
        $payload = $this->buildPayload($payment);

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->post($this->snapUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $payment->update([
                    'payment_url' => $data['redirect_url'],
                    'midtrans_transaction_id' => $payment->payment_code,
                ]);

                return $data;
            }

            throw new \Exception('Failed to create Midtrans transaction: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Midtrans transaction creation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function getTransactionStatus($orderId)
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->get("{$this->apiUrl}/{$orderId}/status");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to get transaction status: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Get Midtrans transaction status failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)
    {
        $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return $mySignatureKey === $signatureKey;
    }

    protected function buildPayload(Payment $payment)
    {
        $payable = $payment->payable;
        $itemName = '';

        if ($payable instanceof \App\Models\Course) {
            $itemName = "Course: {$payable->title}";
        } elseif ($payable instanceof \App\Models\Bootcamp) {
            $itemName = "Bootcamp: {$payable->title}";
        }

        return [
            'transaction_details' => [
                'order_id' => $payment->payment_code,
                'gross_amount' => $payment->total,
            ],
            'customer_details' => [
                'first_name' => $payment->user->name,
                'email' => $payment->user->email,
            ],
            'item_details' => [
                [
                    'id' => $payable->id,
                    'price' => $payment->amount,
                    'quantity' => 1,
                    'name' => $itemName,
                ]
            ],
            'callbacks' => [
                'finish' => route('payment.finish'),
            ],
            'expiry' => [
                'unit' => 'hour',
                'duration' => 24
            ]
        ];
    }
}
