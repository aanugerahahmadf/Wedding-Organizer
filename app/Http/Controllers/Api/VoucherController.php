<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;

class VoucherController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Voucher::where('is_active', true)
                ->where(function ($q): void {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })->get(),
        ]);
    }

    public function validateVoucher(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $voucher = Voucher::where('code', $request->code)
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })->first();

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher tidak valid atau sudah kadaluarsa',
            ], 404);
        }

        if ($request->amount < $voucher->min_purchase) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minimum pembelian untuk voucher ini adalah Rp ' . number_format($voucher->min_purchase, 0, ',', '.'),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => $voucher,
        ]);
    }
}
