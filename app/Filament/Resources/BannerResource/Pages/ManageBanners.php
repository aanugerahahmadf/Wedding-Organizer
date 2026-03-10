<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Exports\BannerExporter;
use App\Filament\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBanners extends ManageRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(BannerExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Banner')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Banner Ditambahkan')
                        ->body('Banner baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
