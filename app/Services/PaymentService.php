<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
    }

    public function createOrder(int $amountPaise, string $currency = 'INR', array $notes = []): array
    {
        $order = $this->api->order->create([
            'amount'          => $amountPaise,
            'currency'        => $currency,
            'receipt'         => 'spliqo_' . uniqid(),
            'notes'           => $notes,
            'payment_capture' => 1,
        ]);

        return [
            'order_id' => $order->id,
            'amount'   => $order->amount,
            'currency' => $order->currency,
            'key_id'   => config('services.razorpay.key_id'),
        ];
    }

    public function verifyAndCapture(Transaction $transaction, array $payload): bool
    {
        $orderId   = $payload['razorpay_order_id']   ?? '';
        $paymentId = $payload['razorpay_payment_id'] ?? '';
        $signature = $payload['razorpay_signature']  ?? '';

        try {
            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Razorpay signature mismatch', [
                'transaction_id' => (string) $transaction->_id,
                'error'          => $e->getMessage(),
            ]);
            return false;
        }

        $transaction->update([
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature'  => $signature,
            'status'              => 'completed',
            'settled_at'          => now(),
        ]);

        return true;
    }

    public function handleWebhook(array $payload, string $signature, string $webhookSecret): bool
    {
        try {
            $this->api->utility->verifyWebhookSignature(
                json_encode($payload),
                $signature,
                $webhookSecret
            );
        } catch (\Throwable $e) {
            Log::warning('Razorpay webhook signature mismatch', ['error' => $e->getMessage()]);
            return false;
        }

        $event     = $payload['event']              ?? '';
        $paymentId = $payload['payload']['payment']['entity']['id']       ?? null;
        $orderId   = $payload['payload']['payment']['entity']['order_id'] ?? null;

        if (!$paymentId || !$orderId) {
            return false;
        }

        $transaction = Transaction::where('razorpay_order_id', $orderId)->first();
        if (!$transaction) {
            return false;
        }

        if ($event === 'payment.captured') {
            $transaction->update([
                'razorpay_payment_id' => $paymentId,
                'status'              => 'completed',
                'settled_at'          => now(),
                'webhook_payload'     => $payload,
            ]);
        } elseif ($event === 'payment.failed') {
            $transaction->update([
                'status'          => 'failed',
                'failure_reason'  => $payload['payload']['payment']['entity']['error_description'] ?? 'Payment failed',
                'webhook_payload' => $payload,
            ]);
        }

        return true;
    }
}
