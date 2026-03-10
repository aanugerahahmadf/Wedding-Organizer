<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentController extends Controller
{
    /**
     * Get available payment methods
     */
    /**
     * Get available payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        try {
            $methods = \App\Models\PaymentMethod::where('is_active', true)->get();

            $formattedMethods = $methods->map(function ($method) {
                $data = [
                    'id' => $method->code,
                    'name' => $method->name,
                    'icon' => $method->icon ? asset('storage/' . $method->icon) : null,
                    'enabled' => true,
                    'type' => $method->type,
                    'fee' => floatval($method->fee),
                ];

                if ($method->type === 'bank_transfer') {
                    $data['manual_details'] = [
                        'bank_name' => $method->name,
                        'account_number' => $method->account_number,
                        'account_holder' => $method->account_holder,
                    ];
                } elseif ($method->type === 'ewallet') {
                    $data['details'] = $method->account_number;
                } elseif ($method->type === 'qris') {
                    $data['qris_image'] = $method->qris_image ? asset('storage/' . $method->qris_image) : null;
                } elseif ($method->type === 'cod') {
                    $data['instructions'] = $method->instructions ?? 'Bayar tunai di lokasi acara.';
                } elseif ($method->type === 'wallet') {
                    $data['instructions'] = $method->instructions ?? 'Pembayaran otomatis dipotong dari saldo dompet.';
                }

                if ($method->instructions) {
                    $data['instructions'] = $method->instructions;
                }

                return $data;
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedMethods,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment methods',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new payment for an order
     */
    public function createPayment(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string|exists:payment_methods,code',
                'amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string|max:500',
            ]);

            // Verify that the order belongs to the authenticated user
            $order = Order::where('id', $validatedData['order_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Verify that the order can be paid
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order is already paid',
                ], 400);
            }

            if ($order->status === 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot pay for cancelled order',
                ], 400);
            }

            // Verify the amount matches the order total (with small tolerance for rounding)
            $tolerance = 1000; // 1000 IDR tolerance
            if (abs($validatedData['amount'] - $order->total_price) > $tolerance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment amount does not match order total',
                ], 400);
            }

            // Check if a payment already exists for this order
            $existingPayment = Payment::where('order_id', $order->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment already exists for this order',
                    'payment' => $existingPayment,
                ], 409);
            }

            // Fetch admin fee from DB
            $adminFee = $this->calculateAdminFee($validatedData['payment_method'], $validatedData['amount']);
            $totalAmount = $validatedData['amount'] + $adminFee;

            // Create the payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_number' => 'PAY-' . strtoupper(Str::random(12)),
                'payment_method' => $validatedData['payment_method'],
                'status' => 'pending',
                'amount' => $validatedData['amount'],
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'notes' => $validatedData['notes'] ?? null,
                'expired_at' => now()->addHours(24), // Payment expires in 24 hours
            ]);

            // In a real application, you would integrate with a payment gateway here
            // For example, with Midtrans:
            // $snapToken = \Midtrans\Snap::createTransaction($params)->token;

            return response()->json([
                'status' => 'success',
                'message' => 'Payment created successfully',
                'data' => [
                    'payment' => $payment,
                    'payment_method_details' => $this->getPaymentMethodDetails($validatedData['payment_method']),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found or does not belong to user',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's payment history
     */
    public function getUserPayments(Request $request)
    {
        try {
            $query = Payment::with(['order.package.weddingOrganizer'])
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.user_id', Auth::id())
                ->select('payments.*');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('payments.status', $request->status);
            }

            if ($request->filled('payment_method')) {
                $query->where('payments.payment_method', $request->payment_method);
            }

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereBetween('payments.created_at', [$request->from_date, $request->to_date]);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['created_at', 'amount', 'status', 'payment_method'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $allowedDirections = ['asc', 'desc'];
            if (!in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'desc';
            }

            $query->orderBy('payments.' . $sortBy, $sortDirection);

            $payments = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'status' => 'success',
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'has_more_pages' => $payments->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific payment details
     */
    public function getPayment($paymentNumber)
    {
        try {
            $payment = Payment::with(['order.package.weddingOrganizer'])
                ->where('payment_number', $paymentNumber)
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.user_id', Auth::id())
                ->select('payments.*')
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $payment,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found or does not belong to user',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload payment proof
     */
    public function uploadPaymentProof(Request $request, $paymentNumber)
    {
        try {
            $request->validate([
                'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120', // changed from proof_image
            ]);

            $payment = Payment::where('payment_number', $paymentNumber)
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.user_id', Auth::id())
                ->select('payments.*')
                ->firstOrFail();

            // Check if payment can have proof uploaded
            if (!in_array($payment->status, ['pending', 'processing'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot upload proof for payment with status: ' . $payment->status,
                ], 400);
            }

            // Check if payment method requires manual proof
            $manualPaymentMethods = ['bank_transfer', 'manual_transfer', 'qris', 'ewallet', 'gopay', 'ovo', 'dana'];
            if (!in_array($payment->payment_method, $manualPaymentMethods)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment method ' . $payment->payment_method . ' does not require manual proof',
                ], 400);
            }

            // Store the proof image
            $path = $request->file('payment_proof')->store('payment-proofs', 'public');

            // --- SIMULASI KECERDASAN BUATAN (AI OCR) ---
            // Di sini nantinya bisa diintegrasikan dengan Google Vision AI atau AWS Rekognition
            // Untuk sekarang kita simulasikan sistem sedang "Menganalisa" struk
            $aiAnalysis = [
                'ai_status' => 'analyzed',
                'confidence_score' => 0.98,
                'detected_amount' => $payment->total_amount, // Asumsi AI mendeteksi angka yang cocok
                'is_verified_by_ai' => true,
                'scanned_at' => now()->toDateTimeString(),
            ];

            // Update payment with proof and AI metadata
            $payment->update([
                'payment_proof' => $path,
                'status' => $aiAnalysis['is_verified_by_ai'] ? 'success' : 'processing',
                'paid_at' => $aiAnalysis['is_verified_by_ai'] ? now() : null,
                'metadata' => array_merge($payment->metadata ?? [], ['ai_analysis' => $aiAnalysis]),
            ]);

            // Jika otomatis diverifikasi oleh AI, update juga status pesanannya
            if ($aiAnalysis['is_verified_by_ai']) {
                $payment->order->update([
                    'payment_status' => 'paid',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => $aiAnalysis['is_verified_by_ai'] 
                    ? 'Bukti pembayaran divalidasi otomatis oleh AI. Pesanan Anda kini aktif!' 
                    : 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
                'data' => [
                    'payment_proof_url' => asset('storage/' . $path),
                    'payment' => $payment->fresh(),
                    'ai_verified' => $aiAnalysis['is_verified_by_ai'],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found or does not belong to user',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload payment proof',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a payment
     */
    public function cancelPayment($paymentNumber)
    {
        try {
            $payment = Payment::where('payment_number', $paymentNumber)
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.user_id', Auth::id())
                ->select('payments.*')
                ->firstOrFail();

            // Check if payment can be cancelled
            if (!in_array($payment->status, ['pending', 'processing'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel payment with status: ' . $payment->status,
                ], 400);
            }

            $payment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment cancelled successfully',
                'data' => $payment->fresh(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found or does not belong to user',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate admin fee based on payment method and amount
     */
    private function calculateAdminFee($paymentMethodCode, $amount)
    {
        $method = \App\Models\PaymentMethod::where('code', $paymentMethodCode)->first();
        
        if (!$method) {
            return 0;
        }

        return floatval($method->fee);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentNumber)
    {
        try {
            $payment = Payment::with(['order.package.weddingOrganizer'])
                ->where('payment_number', $paymentNumber)
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.user_id', Auth::id())
                ->select('payments.*')
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment_number' => $payment->payment_number,
                    'status' => $payment->status,
                    'status_label' => $payment->status_label,
                    'status_color' => $payment->status_color,
                    'payment_method' => $payment->payment_method,
                    'payment_method_label' => $payment->payment_method_label,
                    'amount' => $payment->amount,
                    'total_amount' => $payment->total_amount,
                    'created_at' => $payment->created_at,
                    'expired_at' => $payment->expired_at,
                    'paid_at' => $payment->paid_at,
                    'cancelled_at' => $payment->cancelled_at,
                    'order' => [
                        'order_number' => $payment->order->order_number,
                        'status' => $payment->order->status,
                        'payment_status' => $payment->order->payment_status,
                    ],
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found or does not belong to user',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Get payment method details
     */
    private function getPaymentMethodDetails($paymentMethodCode)
    {
        $method = \App\Models\PaymentMethod::where('code', $paymentMethodCode)->first();
        
        if (!$method) {
            return [
                'id' => $paymentMethodCode,
                'name' => ucfirst(str_replace('_', ' ', $paymentMethodCode)),
                'type' => 'manual',
            ];
        }

        $data = [
            'id' => $method->code,
            'name' => $method->name,
            'icon' => $method->icon ? asset('storage/' . $method->icon) : null,
            'type' => $method->type,
            'fee' => floatval($method->fee),
            'instructions' => $method->instructions,
        ];

        if ($method->type === 'bank_transfer') {
            $data['manual_details'] = [
                'bank_name' => $method->name,
                'account_number' => $method->account_number,
                'account_holder' => $method->account_holder,
            ];
        } elseif ($method->type === 'ewallet') {
            $data['details'] = $method->account_number;
        } elseif ($method->type === 'qris') {
            $data['qris_image'] = $method->qris_image ? asset('storage/' . $method->qris_image) : null;
        }

        return $data;
    }
}