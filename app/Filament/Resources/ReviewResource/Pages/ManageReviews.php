<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Exports\ReviewExporter;
use App\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageReviews extends ManageRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(ReviewExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Review')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Review Ditambahkan')
                        ->body('Review baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
