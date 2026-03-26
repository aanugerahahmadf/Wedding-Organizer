<?php

namespace App\Filament\Admin\Exports;

use App\Models\Package;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PackageExporter extends Exporter
{
    protected static ?string $model = Package::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ID')),
            ExportColumn::make('wedding_organizer_id'),
            ExportColumn::make('category_id'),
            ExportColumn::make('name'),
            ExportColumn::make('slug'),
            ExportColumn::make('description'),
            ExportColumn::make('price'),
            ExportColumn::make('features'),
            ExportColumn::make('theme'),
            ExportColumn::make('color'),
            ExportColumn::make('min_capacity'),
            ExportColumn::make('max_capacity'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your package export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
