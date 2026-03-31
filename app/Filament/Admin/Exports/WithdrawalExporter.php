<?php

namespace App\Filament\Admin\Exports;

use App\Models\Withdrawal;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class WithdrawalExporter extends Exporter
{
    protected static ?string $model = Withdrawal::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ID')),
            ExportColumn::make('user.full_name')
                ->label(__('Pengguna')),
            ExportColumn::make('reference_number')
                ->label(__('Nomor Referensi')),
            ExportColumn::make('amount')
                ->label(__('Jumlah')),
            ExportColumn::make('bank_name')
                ->label(__('Nama Bank')),
            ExportColumn::make('account_number')
                ->label(__('Nomor Rekening')),
            ExportColumn::make('account_holder')
                ->label(__('Pemilik Rekening')),
            ExportColumn::make('status')
                ->label(__('Status')),
            ExportColumn::make('notes')
                ->label(__('Catatan Pengguna')),
            ExportColumn::make('admin_notes')
                ->label(__('Catatan Admin')),
            ExportColumn::make('created_at')
                ->label(__('Dibuat Pada')),
            ExportColumn::make('updated_at')
                ->label(__('Diperbarui Pada')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your withdrawal export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
