<?php

namespace App\Filament\Admin\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ID')),
            ExportColumn::make('order.user.full_name')
                ->label(__('Pelanggan')),
            ExportColumn::make('order.order_number')
                ->label(__('ID Pesanan')),
            ExportColumn::make('payment_number')
                ->label(__('Nomor Pembayaran')),
            ExportColumn::make('payment_method')
                ->label(__('Metode Pembayaran')),
            ExportColumn::make('status')
                ->label(__('Status')),
            ExportColumn::make('amount')
                ->label(__('Jumlah')),
            ExportColumn::make('admin_fee')
                ->label(__('Biaya Admin')),
            ExportColumn::make('total_amount')
                ->label(__('Total Jumlah')),
            ExportColumn::make('payment_gateway')
                ->label(__('Payment Gateway')),
            ExportColumn::make('transaction_id')
                ->label(__('ID Transaksi')),
            ExportColumn::make('payment_url')
                ->label(__('URL Pembayaran')),
            ExportColumn::make('bank_name')
                ->label(__('Nama Bank')),
            ExportColumn::make('account_number')
                ->label(__('Nomor Rekening')),
            ExportColumn::make('account_holder')
                ->label(__('Pemilik Rekening')),
            ExportColumn::make('payment_proof')
                ->label(__('Bukti Pembayaran')),
            ExportColumn::make('paid_at')
                ->label(__('Dibayar Pada')),
            ExportColumn::make('expired_at')
                ->label(__('Kedaluwarsa Pada')),
            ExportColumn::make('cancelled_at')
                ->label(__('Dibatalkan Pada')),
            ExportColumn::make('notes')
                ->label(__('Catatan')),
            ExportColumn::make('metadata')
                ->label(__('Metadata')),
            ExportColumn::make('created_at')
                ->label(__('Dibuat Pada')),
            ExportColumn::make('updated_at')
                ->label(__('Diperbarui Pada')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payment export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
