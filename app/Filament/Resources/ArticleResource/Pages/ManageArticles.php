<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Exports\ArticleExporter;
use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageArticles extends ManageRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(ArticleExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Artikel')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Artikel Ditambahkan')
                        ->body('Artikel baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
