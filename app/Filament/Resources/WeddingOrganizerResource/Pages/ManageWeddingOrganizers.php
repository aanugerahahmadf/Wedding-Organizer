<?php

namespace App\Filament\Resources\WeddingOrganizerResource\Pages;

use App\Filament\Exports\WeddingOrganizerExporter;
use App\Filament\Resources\WeddingOrganizerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWeddingOrganizers extends ManageRecords
{
    protected static string $resource = WeddingOrganizerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(WeddingOrganizerExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Studio')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Studio Ditambahkan')
                        ->body('Data studio rias baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
