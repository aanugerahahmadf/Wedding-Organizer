<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Topup;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function getWalletData(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'balance' => $request->user()->balance,
            ],
        ]);
    }

    public function getHistory(Request $request)
    {
        $history = Topup::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    public function requestTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $amount = $request->amount;

            // Generate reference number
            $referenceNumber = 'TOPUP-'.strtoupper(Str::random(10));

            // Fetch admin fee from PaymentMethod model
            $method = PaymentMethod::where('code', $request->payment_method)->first(['*']);
            $adminFee = $method ? floatval($method->fee) : 0;

            $totalAmount = $amount + $adminFee;

            $topup = Topup::create([
                'user_id' => $user->id,
                'reference_number' => $referenceNumber,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('Permintaan topup berhasil dibuat'),
                'data' => $topup,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|max:2048',
        ]);

        $topup = Topup::where('user_id', $request->user()->id)->findOrFail($id, ['*']);

        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payment-proofs', 'public');
            $topup->update([
                'payment_proof' => $path,
                'status' => 'pending', // Re-verify if needed
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Bukti pembayaran berhasil diunggah'),
            'data' => $topup,
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_holder' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $amount = $request->amount;

            if ($user->balance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Saldo tidak cukup'),
                ], 400);
            }

            $referenceNumber = 'WD-'.strtoupper(Str::random(10));

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'reference_number' => $referenceNumber,
                'amount' => $amount,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_holder' => $request->account_holder,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Deduct balance immediately
            $user->decrement('balance', $amount);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('Permintaan penarikan berhasil dibuat'),
                'data' => $withdrawal,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getWithdrawalHistory(Request $request)
    {
        $history = Withdrawal::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }
}
