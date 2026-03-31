<?php

namespace App\Filament\Admin\Exports;

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
                ->label(__('ID')),
            ExportColumn::make('author_id')
                ->label(__('Penulis')),
            ExportColumn::make('title')
                ->label(__('Judul')),
            ExportColumn::make('slug')
                ->label(__('Slug')),
            ExportColumn::make('content')
                ->label(__('Konten')),
            ExportColumn::make('image_url')
                ->label(__('URL Gambar')),
            ExportColumn::make('is_published')
                ->label(__('Status Terbit')),
            ExportColumn::make('published_at')
                ->label(__('Tanggal Terbit')),
            ExportColumn::make('created_at')
                ->label(__('Dibuat Pada')),
            ExportColumn::make('updated_at')
                ->label(__('Diperbarui Pada')),
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
