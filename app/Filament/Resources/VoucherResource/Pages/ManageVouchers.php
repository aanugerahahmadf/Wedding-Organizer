<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Exports\VoucherExporter;
use App\Filament\Resources\VoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageVouchers extends ManageRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(VoucherExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Voucher')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Voucher Ditambahkan')
                        ->body('Voucher baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
