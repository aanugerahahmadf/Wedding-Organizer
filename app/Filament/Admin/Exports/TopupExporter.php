<?php

namespace App\Filament\Admin\Exports;

use App\Models\Topup;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TopupExporter extends Exporter
{
    protected static ?string $model = Topup::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ID')),
            ExportColumn::make('user.id')
                ->label(__('ID Pengguna')),
            ExportColumn::make('reference_number')
                ->label(__('Nomor Referensi')),
            ExportColumn::make('amount')
                ->label(__('Jumlah')),
            ExportColumn::make('admin_fee')
                ->label(__('Biaya Admin')),
            ExportColumn::make('total_amount')
                ->label(__('Total Jumlah')),
            ExportColumn::make('payment_method')
                ->label(__('Metode Pembayaran')),
            ExportColumn::make('status')
                ->label(__('Status')),
            ExportColumn::make('payment_url')
                ->label(__('URL Pembayaran')),
            ExportColumn::make('payment_proof')
                ->label(__('Bukti Pembayaran')),
            ExportColumn::make('paid_at')
                ->label(__('Dibayar Pada')),
            ExportColumn::make('notes')
                ->label(__('Catatan')),
            ExportColumn::make('created_at')
                ->label(__('Dibuat Pada')),
            ExportColumn::make('updated_at')
                ->label(__('Diperbarui Pada')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your topup export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
