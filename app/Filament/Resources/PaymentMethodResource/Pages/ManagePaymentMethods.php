<?php

namespace App\Filament\Resources\PaymentMethodResource\Pages;

use App\Filament\Resources\PaymentMethodResource;
use App\Filament\Exports\PaymentMethodExporter;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentMethods extends ManageRecords
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(PaymentMethodExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Metode')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Metode Ditambahkan')
                        ->body('Metode pembayaran baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
