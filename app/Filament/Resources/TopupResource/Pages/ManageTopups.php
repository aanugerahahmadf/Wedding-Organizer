<?php

namespace App\Filament\Resources\TopupResource\Pages;

use App\Filament\Resources\TopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTopups extends ManageRecords
{
    protected static string $resource = TopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(\App\Filament\Exports\TopupExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Topup')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Topup Ditambahkan')
                        ->body('Data topup baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
