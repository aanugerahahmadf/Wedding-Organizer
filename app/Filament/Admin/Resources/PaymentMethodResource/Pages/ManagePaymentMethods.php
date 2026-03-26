<?php

namespace App\Filament\Admin\Resources\PaymentMethodResource\Pages;

use App\Filament\Admin\Exports\PaymentMethodExporter;
use App\Filament\Admin\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

/**
 * @property-read \App\Filament\Resources\PaymentMethodResource $resource
 */
class ManagePaymentMethods extends ManageRecords
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(PaymentMethodExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label(__('Tambah Metode'))
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('Metode Ditambahkan'))
                        ->body(__('Metode pembayaran baru telah berhasil ditambahkan.'))
                ),
        ];
    }
}
