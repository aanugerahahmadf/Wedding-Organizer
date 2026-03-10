<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Bank BCA',
                'type' => 'bank_transfer',
                'code' => 'bca',
                'account_number' => '8760123456',
                'account_holder' => 'PT Devi Make Up Wedding Organizer',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening BCA yang tertera, lalu upload bukti pembayaran di menu Pesanan Saya.',
            ],
            [
                'name' => 'Bank Mandiri',
                'type' => 'bank_transfer',
                'code' => 'mandiri',
                'account_number' => '1370012345678',
                'account_holder' => 'PT Devi Make Up Wedding Organizer',
                'fee' => 0,
                'is_active' => true,
                'instructions' => 'Transfer ke rekening Mandiri yang tertera, lalu upload bukti pembayaran di menu Pesanan Saya.',
            ],
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
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
