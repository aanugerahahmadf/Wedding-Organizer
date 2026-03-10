<?php

namespace App\Filament\Resources\WithdrawalResource\Pages;

use App\Filament\Resources\WithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWithdrawals extends ManageRecords
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(\App\Filament\Exports\WithdrawalExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Penarikan')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Penarikan Ditambahkan')
                        ->body('Data penarikan baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
