<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Exports\OrderExporter;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(OrderExporter::class)
                ->label('Ekspor Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Tambah Order')
                ->icon('heroicon-o-plus')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Order Ditambahkan')
                        ->body('Order baru telah berhasil ditambahkan.')
                ),
        ];
    }
}
