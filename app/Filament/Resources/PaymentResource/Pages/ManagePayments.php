<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Exports\PaymentExporter;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(PaymentExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Pembayaran')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Pembayaran Ditambahkan')
                        ->body('Data pembayaran baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
