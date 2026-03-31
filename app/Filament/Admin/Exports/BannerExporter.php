<?php

namespace App\Filament\Admin\Exports;

use App\Models\Banner;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class BannerExporter extends Exporter
{
    protected static ?string $model = Banner::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ID')),
            ExportColumn::make('title')
                ->label(__('Judul')),
            ExportColumn::make('image_url')
                ->label(__('URL Gambar')),
            ExportColumn::make('link_url')
                ->label(__('URL Link')),
            ExportColumn::make('is_active')
                ->label(__('Status Aktif')),
            ExportColumn::make('sort_order')
                ->label(__('Urutan')),
            ExportColumn::make('created_at')
                ->label(__('Dibuat Pada')),
            ExportColumn::make('updated_at')
                ->label(__('Diperbarui Pada')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your banner export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
