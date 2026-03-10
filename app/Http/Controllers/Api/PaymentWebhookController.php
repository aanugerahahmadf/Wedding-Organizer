<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Midtrans payment notification webhook
     */
    public function handleMidtransNotification(Request $request)
    {
        try {
            // Get notification data
            $notificationData = $request->all();

            // Log webhook for debugging
            Log::info('Midtrans Webhook Received', $notificationData);

            // Verify signature (Important for production!)
            // TODO: Implement actual signature verification
            // $this->verifySignature($notificationData);

            $transactionId = $notificationData['transaction_id'] ?? null;
            $orderNumber = $notificationData['order_id'] ?? null;
            $transactionStatus = $notificationData['transaction_status'] ?? null;
            $fraudStatus = $notificationData['fraud_status'] ?? 'accept';

            if (! $transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid notification data',
                ], 400);
            }

            // Find payment by transaction ID
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (! $payment) {
                Log::warning('Payment not found for transaction', ['transaction_id' => $transactionId]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // Update payment metadata
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], $notificationData),
            ]);

            // Handle different transaction statuses
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $this->handleSuccessPayment($payment);
                }
            } elseif ($transactionStatus == 'settlement') {
                $this->handleSuccessPayment($payment);
            } elseif ($transactionStatus == 'pending') {
                $payment->update(['status' => 'pending']);
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $this->handleFailedPayment($payment, $transactionStatus);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessPayment(Payment $payment)
    {
        if ($payment->status !== 'success') {
            $payment->markAsSuccess();

            // Send notification to user
            // TODO: Implement notification system
            Log::info('Payment successful', [
                'payment_number' => $payment->payment_number,
                'order_id' => $payment->order_id,
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment(Payment $payment, $reason)
    {
        $payment->markAsFailed($reason);

        Log::info('Payment failed', [
            'payment_number' => $payment->payment_number,
            'reason' => $reason,
        ]);
    }

    /**
     * Verify Midtrans signature (Production use)
     */
    private function verifySignature($data)
    {
        // TODO: Implement actual signature verification
        // $serverKey = config('midtrans.server_key');
        // $orderId = $data['order_id'];
        // $statusCode = $data['status_code'];
        // $grossAmount = $data['gross_amount'];
        // $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // if ($data['signature_key'] !== $signatureKey) {
        //     throw new \Exception('Invalid signature');
        // }
    }

    /**
     * Manual payment verification (for bank transfer)
     */
    public function verifyManualPayment(Request $request, $paymentId)
    {
        $request->validate([
            'status' => 'required|in:success,failed',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($paymentId);

        if ($payment->payment_method !== 'bank_transfer') {
            return response()->json([
                'success' => false,
                'message' => 'Manual verification only for bank transfer',
            ], 400);
        }

        if ($request->status === 'success') {
            $payment->markAsSuccess();
            $message = 'Payment verified and approved';
        } else {
            $payment->markAsFailed($request->notes);
            $message = 'Payment rejected';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $payment->fresh(),
        ]);
    }
}
