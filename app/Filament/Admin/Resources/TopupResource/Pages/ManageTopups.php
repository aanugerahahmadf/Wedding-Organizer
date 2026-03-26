<?php

namespace App\Filament\Admin\Resources\TopupResource\Pages;

use App\Filament\Admin\Exports\TopupExporter;
use App\Filament\Admin\Resources\TopupResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

/**
 * @property-read \App\Filament\Resources\TopupResource $resource
 */
class ManageTopups extends ManageRecords
{
    protected static string $resource = TopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(TopupExporter::class)
                ->label(__('Ekspor Data'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make()
                ->label(__('Tambah Topup'))
                ->icon('heroicon-o-plus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('Topup Ditambahkan'))
                        ->body(__('Data topup baru telah berhasil ditambahkan.'))
                ),
        ];
    }
}
