<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            // --- TRANSFER BANK ---
            [
                'name' => 'Bank BCA',
                'type' => 'bank_transfer',
                'code' => 'bca',
                'account_number' => '8760123456',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening BCA yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank Mandiri',
                'type' => 'bank_transfer',
                'code' => 'mandiri',
                'account_number' => '1370012345678',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening Mandiri yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank BNI',
                'type' => 'bank_transfer',
                'code' => 'bni',
                'account_number' => '0987123456',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening BNI yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank BRI',
                'type' => 'bank_transfer',
                'code' => 'bri',
                'account_number' => '012301004567890',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening BRI yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank CIMB Niaga',
                'type' => 'bank_transfer',
                'code' => 'cimb',
                'account_number' => '800123456700',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening CIMB Niaga yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank Permata',
                'type' => 'bank_transfer',
                'code' => 'permata',
                'account_number' => '4101234567',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening Permata yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank Danamon',
                'type' => 'bank_transfer',
                'code' => 'danamon',
                'account_number' => '003123456789',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening Danamon yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Bank BSI (Syariah)',
                'type' => 'bank_transfer',
                'code' => 'bsi',
                'account_number' => '7001234567',
                'account_holder' => 'PT Devi Make Up',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening BSI yang tertera, lalu upload bukti pembayaran.',
            ],

            // --- E-WALLETS ---
            [
                'name' => 'GoPay',
                'type' => 'ewallet',
                'code' => 'gopay',
                'account_number' => '081234567890',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke nomor GoPay yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'OVO',
                'type' => 'ewallet',
                'code' => 'ovo',
                'account_number' => '081234567890',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke nomor OVO yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'DANA',
                'type' => 'ewallet',
                'code' => 'dana',
                'account_number' => '081234567890',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke nomor DANA yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'ShopeePay',
                'type' => 'ewallet',
                'code' => 'shopeepay',
                'account_number' => '081234567890',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke nomor ShopeePay yang tertera, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'LinkAja',
                'type' => 'ewallet',
                'code' => 'linkaja',
                'account_number' => '081234567890',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke nomor LinkAja yang tertera, lalu upload bukti pembayaran.',
            ],

            // --- LAINNYA ---
            [
                'name' => 'QRIS',
                'type' => 'qris',
                'code' => 'qris',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Scan kode QRIS yang ditampilkan untuk melakukan pembayaran, lalu upload bukti pembayaran.',
            ],
            [
                'name' => 'Cash On Delivery (COD)',
                'type' => 'cod',
                'code' => 'cod',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Pembayaran dilakukan secara tunai di lokasi acara pada hari H. Pastikan Anda menyiapkan uang pas sesuai total pembayaran.',
            ],
            [
                'name' => 'Saldo Dompet',
                'type' => 'wallet',
                'code' => 'wallet',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Pembayaran akan otomatis dipotong dari saldo dompet Anda. Pastikan saldo mencukupi sebelum melakukan pemesanan.',
            ],
        ];

        foreach ($methods as $method) {
            $bank = \App\Models\Bank::where('code', $method['code'])->first();
            if ($bank) {
                $method['bank_id'] = $bank->id;
            }

            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
