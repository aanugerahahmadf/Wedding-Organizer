<?php

namespace App\Filament\Admin\Exports;

use App\Models\WeddingOrganizer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class WeddingOrganizerExporter extends Exporter
{
    protected static ?string $model = WeddingOrganizer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ID')),
            ExportColumn::make('user_id')
                ->label(__('ID Pengguna')),
            ExportColumn::make('name')
                ->label(__('Nama WO')),
            ExportColumn::make('slug')
                ->label(__('Slug')),
            ExportColumn::make('description')
                ->label(__('Deskripsi')),
            ExportColumn::make('address')
                ->label(__('Alamat')),
            ExportColumn::make('latitude')
                ->label(__('Latitude')),
            ExportColumn::make('longitude')
                ->label(__('Longitude')),
            ExportColumn::make('rating')
                ->label(__('Rating')),
            ExportColumn::make('is_verified')
                ->label(__('Status Verifikasi')),
            ExportColumn::make('created_at')
                ->label(__('Dibuat Pada')),
            ExportColumn::make('updated_at')
                ->label(__('Diperbarui Pada')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your wedding organizer export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
