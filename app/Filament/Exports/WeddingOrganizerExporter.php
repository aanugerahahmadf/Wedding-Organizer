<?php

namespace App\Filament\Exports;

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
                ->label('ID'),
            ExportColumn::make('user_id'),
            ExportColumn::make('name'),
            ExportColumn::make('slug'),
            ExportColumn::make('description'),
            ExportColumn::make('address'),
            ExportColumn::make('latitude'),
            ExportColumn::make('longitude'),
            ExportColumn::make('rating'),
            ExportColumn::make('is_verified'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
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
