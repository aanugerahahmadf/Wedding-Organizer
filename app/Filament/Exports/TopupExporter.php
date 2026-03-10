<?php

namespace App\Filament\Exports;

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
                ->label('ID'),
            ExportColumn::make('user.id'),
            ExportColumn::make('reference_number'),
            ExportColumn::make('amount'),
            ExportColumn::make('admin_fee'),
            ExportColumn::make('total_amount'),
            ExportColumn::make('payment_method'),
            ExportColumn::make('status'),
            ExportColumn::make('payment_url'),
            ExportColumn::make('payment_proof'),
            ExportColumn::make('paid_at'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your topup export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
