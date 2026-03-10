<?php

namespace App\Filament\Exports;

use App\Models\PaymentMethod;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentMethodExporter extends Exporter
{
    protected static ?string $model = PaymentMethod::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Nama Metode'),
            ExportColumn::make('type')
                ->label('Tipe'),
            ExportColumn::make('code')
                ->label('Kode'),
            ExportColumn::make('account_number')
                ->label('Nomor Akun'),
            ExportColumn::make('account_holder')
                ->label('Nama Pemilik'),
            ExportColumn::make('fee')
                ->label('Biaya'),
            ExportColumn::make('is_active')
                ->label('Status Aktif'),
            ExportColumn::make('created_at')
                ->label('Dibuat Pada'),
            ExportColumn::make('updated_at')
                ->label('Diperbarui Pada'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor metode pembayaran telah selesai dan ' . number_format($export->successful_rows) . ' ' . str('baris')->plural($export->successful_rows) . ' berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
