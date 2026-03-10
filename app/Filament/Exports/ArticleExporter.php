<?php

namespace App\Filament\Exports;

use App\Models\Article;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ArticleExporter extends Exporter
{
    protected static ?string $model = Article::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('author_id'),
            ExportColumn::make('title'),
            ExportColumn::make('slug'),
            ExportColumn::make('content'),
            ExportColumn::make('image_url'),
            ExportColumn::make('is_published'),
            ExportColumn::make('published_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your article export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
